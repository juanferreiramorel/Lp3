-- TRIGGERS primero
DROP TRIGGER IF EXISTS trg_insert_ctas_cobrar ON public.ventas;
DROP TRIGGER IF EXISTS trg_update_ctas_cobrar ON public.ventas;
DROP TRIGGER IF EXISTS trg_delete_ctas_cobrar ON public.ventas;

-- Funciones (si existen)
DROP FUNCTION IF EXISTS public._generar_ctas_cobrar_core(INT,INT,DATE,NUMERIC,INT,INT);
DROP FUNCTION IF EXISTS public.generar_ctas_cobrar();
DROP FUNCTION IF EXISTS public.actualizar_ctas_cobrar();
DROP FUNCTION IF EXISTS public.borrar_ctas_cobrar();

CREATE OR REPLACE FUNCTION public._generar_ctas_cobrar_core(
    p_id_cliente INT,
    p_id_venta   INT,
    p_fecha_venta DATE,
    p_total NUMERIC,
    p_cantidad_cuota INT,
    p_intervalo INT
) RETURNS VOID AS $$
DECLARE
    monto_a_cobrar NUMERIC;
    vencimiento DATE;
    cont INTEGER;
    dias INTERVAL;
    primer_vto DATE;
BEGIN
    IF COALESCE(p_cantidad_cuota,0) <= 0 OR COALESCE(p_intervalo,0) <= 0 THEN
        RETURN;
    END IF;

    monto_a_cobrar := ROUND(p_total / p_cantidad_cuota);
    dias := (p_intervalo || ' days')::INTERVAL;
    primer_vto := p_fecha_venta + dias;

    FOR cont IN 1..p_cantidad_cuota LOOP
        vencimiento := primer_vto + ((cont - 1) * dias);
        INSERT INTO public.cuentas_a_cobrar(
            id_cliente, id_venta, vencimiento, importe, nro_cuota, estado
        ) VALUES (
            p_id_cliente, p_id_venta, vencimiento, monto_a_cobrar, cont, 'PENDIENTE'
        );
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Funcion Para Inser t--
CREATE OR REPLACE FUNCTION public.generar_ctas_cobrar()
RETURNS trigger AS $$
BEGIN
    IF NEW.condicion_venta = 'CREDITO' THEN
        PERFORM public._generar_ctas_cobrar_core(
            NEW.id_cliente, NEW.id_venta, NEW.fecha_venta,
            NEW.total, NEW.cantidad_cuota, NEW.intervalo
        );
    END IF;
    RETURN NEW; -- en AFTER es ignorado, pero mantenemos NEW por claridad
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_insert_ctas_cobrar
AFTER INSERT ON public.ventas
FOR EACH ROW
EXECUTE PROCEDURE public.generar_ctas_cobrar();


CREATE OR REPLACE FUNCTION public.trg_ventas_ctascobrar_guardas()
RETURNS trigger AS
$$
DECLARE
  hay_no_pendiente  boolean;
  cambio_credito    boolean;
  cambio_parametros boolean;
BEGIN
  -- Salida temprana si no hay cambios reales
  IF to_jsonb(NEW) IS NOT DISTINCT FROM to_jsonb(OLD) THEN
    RETURN NEW;
  END IF;

  -- ¿Alguna cuota distinta de PENDIENTE?
  SELECT EXISTS (
    SELECT 1
    FROM public.cuentas_a_cobrar c
    WHERE c.id_venta = OLD.id_venta
      AND c.estado <> 'PENDIENTE'
  ) INTO hay_no_pendiente;

  -- 1) Anulación: si cambia a ANULADO
  IF NEW.estado = 'ANULADO' AND OLD.estado IS DISTINCT FROM 'ANULADO' THEN
    IF hay_no_pendiente THEN
      RAISE EXCEPTION
        'No se puede ANULAR la venta %: existen cuotas con estado diferente a PENDIENTE.',
        OLD.factura_nro;
    END IF;

    DELETE FROM public.cuentas_a_cobrar WHERE id_venta = OLD.id_venta;
    RETURN NEW;
  END IF;

  -- 2) Cualquier otra modificación: bloquear si hay cuotas no pendientes
  IF hay_no_pendiente THEN
    RAISE EXCEPTION
      'No se puede modificar la venta %: existen cuotas con estado diferente a PENDIENTE.',
      OLD.factura_nro;
  END IF;

  -- 3) Manejo de transiciones y regeneración
  cambio_parametros := (NEW.fecha_venta     IS DISTINCT FROM OLD.fecha_venta)
                    OR (NEW.total           IS DISTINCT FROM OLD.total)
                    OR (NEW.cantidad_cuota  IS DISTINCT FROM OLD.cantidad_cuota)
                    OR (NEW.intervalo       IS DISTINCT FROM OLD.intervalo)
                    OR (NEW.id_cliente      IS DISTINCT FROM OLD.id_cliente);

  -- 3.a) CONTADO -> CREDITO : insertar CxC
  IF (OLD.condicion_venta <> 'CREDITO') AND (NEW.condicion_venta = 'CREDITO') THEN
    -- por si existiera algo residual, limpiamos
    DELETE FROM public.cuentas_a_cobrar WHERE id_venta = OLD.id_venta;

    PERFORM public._generar_ctas_cobrar_core(
      NEW.id_cliente, NEW.id_venta, NEW.fecha_venta,
      NEW.total, NEW.cantidad_cuota, NEW.intervalo
    );

    RETURN NEW;
  END IF;

  -- 3.b) CREDITO -> CONTADO : eliminar CxC
  IF (OLD.condicion_venta = 'CREDITO') AND (NEW.condicion_venta <> 'CREDITO') THEN
    DELETE FROM public.cuentas_a_cobrar WHERE id_venta = OLD.id_venta;
    RETURN NEW;
  END IF;

  -- 3.c) Sigue en CREDITO y cambian parámetros: regenerar CxC
  IF (NEW.condicion_venta = 'CREDITO') AND cambio_parametros THEN
    DELETE FROM public.cuentas_a_cobrar WHERE id_venta = OLD.id_venta;

    PERFORM public._generar_ctas_cobrar_core(
      NEW.id_cliente, NEW.id_venta, NEW.fecha_venta,
      NEW.total, NEW.cantidad_cuota, NEW.intervalo
    );
  END IF;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_ventas_ctascobrar_guardas ON public.ventas;

CREATE TRIGGER trg_ventas_ctascobrar_guardas
BEFORE UPDATE ON public.ventas
FOR EACH ROW
EXECUTE PROCEDURE public.trg_ventas_ctascobrar_guardas();

-- Funcion Para Borrar 
CREATE OR REPLACE FUNCTION public.borrar_ctas_cobrar()
RETURNS trigger AS $$
DECLARE
    hay_pagos BOOLEAN;
BEGIN
    IF OLD.condicion_venta = 'CREDITO' THEN
        SELECT EXISTS(
            SELECT 1 FROM public.cuentas_a_cobrar
            WHERE id_venta = OLD.id_venta
              AND estado <> 'PENDIENTE'
        ) INTO hay_pagos;

        IF hay_pagos THEN
            RAISE EXCEPTION 'No se puede borrar la venta %: existen cuotas con pagos registrados.', OLD.id_venta;
        END IF;

        DELETE FROM public.cuentas_a_cobrar WHERE id_venta = OLD.id_venta;
    END IF;

    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_delete_ctas_cobrar
BEFORE DELETE ON public.ventas
FOR EACH ROW
EXECUTE PROCEDURE public.borrar_ctas_cobrar();
