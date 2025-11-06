DROP TRIGGER IF EXISTS trg_insert_ctas_pagar ON public.compras;
DROP TRIGGER IF EXISTS trg_update_ctas_pagar ON public.compras;
DROP TRIGGER IF EXISTS trg_delete_ctas_pagar ON public.compras;

DROP FUNCTION IF EXISTS public._generar_ctas_pagar_core(INT,INT,DATE,NUMERIC,INT,INT);
DROP FUNCTION IF EXISTS public.generar_ctas_pagar();
DROP FUNCTION IF EXISTS public.actualizar_ctas_pagar();
DROP FUNCTION IF EXISTS public.borrar_ctas_pagar();


-- Funcion Generar --
CREATE OR REPLACE FUNCTION public._generar_ctas_pagar_core(
    p_id_proveedor INT,
    p_id_compra INT,
    p_fecha_compra DATE,
    p_total NUMERIC,
    p_cantidad_cuotas INT,
    p_intervalo INT
) RETURNS VOID AS $$
DECLARE
    monto_a_pagar NUMERIC;
    vencimiento DATE;
    cont INTEGER;
    dias INTERVAL;
    primer_vto DATE;
BEGIN
    IF COALESCE(p_cantidad_cuotas,0) <= 0 OR COALESCE(p_intervalo,0) <= 0 THEN
        RETURN;
    END IF;

    monto_a_pagar := ROUND(p_total / p_cantidad_cuotas);
    dias := (p_intervalo || ' days')::INTERVAL;
    primer_vto := p_fecha_compra + dias;

    FOR cont IN 1..p_cantidad_cuotas LOOP
        vencimiento := primer_vto + ((cont - 1) * dias);
        INSERT INTO public.cuentas_a_pagar(
            id_proveedor, id_compra, vencimiento, importe, nro_cuota, estado
        ) VALUES (
            p_id_proveedor, p_id_compra, vencimiento, monto_a_pagar, cont, 'PENDIENTE'
        );
    END LOOP;
END;
$$ LANGUAGE plpgsql;

-- Funcion Para Insertar
CREATE OR REPLACE FUNCTION public.generar_ctas_pagar()
RETURNS trigger AS $$
BEGIN
    IF NEW.condicion_compra = 'CREDITO' THEN
        PERFORM public._generar_ctas_pagar_core(
            NEW.id_proveedor, NEW.id_compra, NEW.fecha_compra,
            NEW.total, NEW.cantidad_cuotas, NEW.intervalo
        );
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_insert_ctas_pagar
AFTER INSERT ON public.compras
FOR EACH ROW
EXECUTE PROCEDURE public.generar_ctas_pagar();

-- Funcion Para Actualizar
CREATE OR REPLACE FUNCTION public.actualizar_ctas_pagar()
RETURNS trigger AS $$
DECLARE
    hay_pagos BOOLEAN;
    cambia BOOLEAN;
BEGIN
    cambia := (NEW.condicion_compra IS DISTINCT FROM OLD.condicion_compra)
           OR (NEW.fecha_compra     IS DISTINCT FROM OLD.fecha_compra)
           OR (NEW.total            IS DISTINCT FROM OLD.total)
           OR (NEW.cantidad_cuotas  IS DISTINCT FROM OLD.cantidad_cuotas)
           OR (NEW.intervalo        IS DISTINCT FROM OLD.intervalo)
           OR (NEW.id_proveedor     IS DISTINCT FROM OLD.id_proveedor);

    IF NOT cambia THEN
        RETURN NEW;
    END IF;

    IF OLD.condicion_compra = 'CREDITO' OR NEW.condicion_compra = 'CREDITO' THEN
        SELECT EXISTS(
            SELECT 1 FROM public.cuentas_a_pagar
            WHERE id_compra = OLD.id_compra
              AND estado <> 'PENDIENTE'
        ) INTO hay_pagos;

        IF hay_pagos THEN
            RAISE EXCEPTION 'No se puede modificar la compra %: existen cuotas con pagos registrados.', OLD.id_compra;
        END IF;

        DELETE FROM public.cuentas_a_pagar WHERE id_compra = OLD.id_compra;

        IF NEW.condicion_compra = 'CREDITO' THEN
            PERFORM public._generar_ctas_pagar_core(
                NEW.id_proveedor, NEW.id_compra, NEW.fecha_compra,
                NEW.total, NEW.cantidad_cuotas, NEW.intervalo
            );
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

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
