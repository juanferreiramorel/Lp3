DROP TRIGGER IF EXISTS trg_insert_ctas_pagar ON public.compras;
DROP TRIGGER IF EXISTS trg_update_ctas_pagar ON public.compras;
DROP TRIGGER IF EXISTS trg_delete_ctas_pagar ON public.compras;

DROP FUNCTION IF EXISTS public._generar_ctas_pagar_core(INT,INT,DATE,NUMERIC,INT,INT);
DROP FUNCTION IF EXISTS public.generar_ctas_pagar();
DROP FUNCTION IF EXISTS public.actualizar_ctas_pagar();
DROP FUNCTION IF EXISTS public.borrar_ctas_pagar();


-- === Core (ya lo tenés) ===
-- _generar_ctas_pagar_core(...)

-- === AFTER INSERT (ya lo tenés) ===
-- generar_ctas_pagar + trg_insert_ctas_pagar

-- === BEFORE UPDATE: compras -> control de CxP y anulación ===
CREATE OR REPLACE FUNCTION public.actualizar_ctas_pagar()
RETURNS trigger AS
$$
DECLARE
  hay_no_pendiente  boolean;
  cambio_parametros boolean;
BEGIN
  -- Salida temprana si no hay cambios reales
  IF to_jsonb(NEW) IS NOT DISTINCT FROM to_jsonb(OLD) THEN
    RETURN NEW;
  END IF;

  -- ¿Alguna cuota de esta compra con estado <> PENDIENTE?
  SELECT EXISTS (
    SELECT 1
    FROM public.cuentas_a_pagar c
    WHERE c.id_compra = OLD.id_compra
      AND c.estado <> 'PENDIENTE'
  ) INTO hay_no_pendiente;

  -- 1) Anulación: si cambia a ANULADO
  IF NEW.estado = 'ANULADO' AND OLD.estado IS DISTINCT FROM 'ANULADO' THEN
    IF hay_no_pendiente THEN
      RAISE EXCEPTION
        'No se puede ANULAR la compra %: existen cuotas con estado diferente a PENDIENTE.',
        OLD.factura;
    END IF;

    DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;
    RETURN NEW;
  END IF;

  -- 2) Cualquier otra modificación: bloquear si hay cuotas no pendientes
  IF hay_no_pendiente THEN
    RAISE EXCEPTION
      'No se puede modificar la compra %: existen cuotas con estado diferente a PENDIENTE.',
      OLD.factura;
  END IF;

  -- 3) Manejo de transiciones y regeneración
  cambio_parametros := (NEW.fecha_compra     IS DISTINCT FROM OLD.fecha_compra)
                    OR (NEW.total            IS DISTINCT FROM OLD.total)
                    OR (NEW.cantidad_cuotas  IS DISTINCT FROM OLD.cantidad_cuotas)
                    OR (NEW.intervalo        IS DISTINCT FROM OLD.intervalo)
                    OR (NEW.id_proveedor     IS DISTINCT FROM OLD.id_proveedor);

  -- 3.a) CONTADO -> CREDITO : insertar CxP
  IF (OLD.condicion_compra <> 'CREDITO') AND (NEW.condicion_compra = 'CREDITO') THEN
    DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;

    PERFORM public._generar_ctas_pagar_core(
      NEW.id_proveedor, NEW.id_compra, NEW.fecha_compra,
      NEW.total, NEW.cantidad_cuotas, NEW.intervalo
    );

    RETURN NEW;
  END IF;

  -- 3.b) CREDITO -> CONTADO : eliminar CxP
  IF (OLD.condicion_compra = 'CREDITO') AND (NEW.condicion_compra <> 'CREDITO') THEN
    DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;
    RETURN NEW;
  END IF;

  -- 3.c) Sigue en CREDITO y cambian parámetros: regenerar CxP
  IF (NEW.condicion_compra = 'CREDITO') AND cambio_parametros THEN
    DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;

    PERFORM public._generar_ctas_pagar_core(
      NEW.id_proveedor, NEW.id_compra, NEW.fecha_compra,
      NEW.total, NEW.cantidad_cuotas, NEW.intervalo
    );
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- === Trigger ===
DROP TRIGGER IF EXISTS trg_update_ctas_pagar ON public.compras;

CREATE TRIGGER trg_update_ctas_pagar
BEFORE UPDATE ON public.compras
FOR EACH ROW
EXECUTE PROCEDURE public.actualizar_ctas_pagar();

-- Funcion Para Borrar 
CREATE OR REPLACE FUNCTION public.borrar_ctas_pagar()
RETURNS trigger AS $$
DECLARE
    hay_pagos BOOLEAN;
BEGIN
    IF OLD.condicion_compra = 'CREDITO' THEN
        SELECT EXISTS(
            SELECT 1 FROM public.cuentas_a_pagar
            WHERE id_compra = OLD.id_compra
              AND estado <> 'PENDIENTE'
        ) INTO hay_pagos;

        IF hay_pagos THEN
            RAISE EXCEPTION 'No se puede borrar la compra %: existen cuotas con pagos registrados.', OLD.id_compra;
        END IF;

        DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;
    END IF;

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_delete_ctas_pagar
BEFORE DELETE ON public.compras
FOR EACH ROW
EXECUTE PROCEDURE public.borrar_ctas_pagar();
