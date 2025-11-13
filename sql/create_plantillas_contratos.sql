-- ============================================================
-- Script de creación de tablas para Plantillas de Contratos
-- ============================================================

-- Tabla: plantillas_contratos
-- Descripción: Almacena las plantillas de contratos con palabras clave
--              que serán reemplazadas por datos reales del cliente
-- ============================================================

CREATE TABLE IF NOT EXISTS public.plantillas_contratos
(
  id_plantilla_contrato serial NOT NULL,
  nombre_plantilla character varying(255) NOT NULL,
  descripcion text,
  contenido_plantilla text NOT NULL,
  activo boolean DEFAULT true,
  fecha_creacion timestamp DEFAULT CURRENT_TIMESTAMP,
  fecha_modificacion timestamp DEFAULT CURRENT_TIMESTAMP,
  user_id integer,
  CONSTRAINT plantillas_contratos_pkey PRIMARY KEY (id_plantilla_contrato),
  CONSTRAINT plantillas_contratos_user_fkey FOREIGN KEY (user_id)
      REFERENCES public.users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE SET NULL
);

COMMENT ON TABLE public.plantillas_contratos IS 'Plantillas de contratos con palabras clave para reemplazo automático';
COMMENT ON COLUMN public.plantillas_contratos.nombre_plantilla IS 'Nombre descriptivo de la plantilla';
COMMENT ON COLUMN public.plantillas_contratos.contenido_plantilla IS 'Contenido de la plantilla con palabras clave en formato --palabra--';
COMMENT ON COLUMN public.plantillas_contratos.activo IS 'Indica si la plantilla está activa o archivada';

-- ============================================================
-- Tabla: contratos_generados
-- Descripción: Almacena el historial de contratos generados
--              a partir de plantillas para clientes específicos
-- ============================================================

CREATE TABLE IF NOT EXISTS public.contratos_generados
(
  id_contrato_generado serial NOT NULL,
  id_plantilla_contrato integer NOT NULL,
  id_cliente integer NOT NULL,
  contenido_generado text NOT NULL,
  observaciones text,
  fecha_generacion timestamp DEFAULT CURRENT_TIMESTAMP,
  user_id integer,
  CONSTRAINT contratos_generados_pkey PRIMARY KEY (id_contrato_generado),
  CONSTRAINT contratos_generados_plantilla_fkey FOREIGN KEY (id_plantilla_contrato)
      REFERENCES public.plantillas_contratos (id_plantilla_contrato) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT contratos_generados_cliente_fkey FOREIGN KEY (id_cliente)
      REFERENCES public.clientes (id_cliente) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE CASCADE,
  CONSTRAINT contratos_generados_user_fkey FOREIGN KEY (user_id)
      REFERENCES public.users (id) MATCH SIMPLE
      ON UPDATE NO ACTION ON DELETE SET NULL
);

COMMENT ON TABLE public.contratos_generados IS 'Historial de contratos generados a partir de plantillas';
COMMENT ON COLUMN public.contratos_generados.contenido_generado IS 'Contenido del contrato con todas las palabras clave reemplazadas';

-- ============================================================
-- Índices para mejorar el rendimiento
-- ============================================================

CREATE INDEX IF NOT EXISTS idx_plantillas_contratos_activo ON public.plantillas_contratos(activo);
CREATE INDEX IF NOT EXISTS idx_plantillas_contratos_user ON public.plantillas_contratos(user_id);
CREATE INDEX IF NOT EXISTS idx_contratos_generados_cliente ON public.contratos_generados(id_cliente);
CREATE INDEX IF NOT EXISTS idx_contratos_generados_plantilla ON public.contratos_generados(id_plantilla_contrato);
CREATE INDEX IF NOT EXISTS idx_contratos_generados_fecha ON public.contratos_generados(fecha_generacion);

-- ============================================================
-- Datos de ejemplo (opcional)
-- ============================================================

-- Plantilla de ejemplo: Contrato de Prestación de Servicios
INSERT INTO public.plantillas_contratos (nombre_plantilla, descripcion, contenido_plantilla, user_id)
VALUES (
  'Contrato de Prestación de Servicios',
  'Plantilla estándar para contratos de servicios',
  'CONTRATO DE PRESTACIÓN DE SERVICIOS

Entre --nombre_completo--, con CI N° --ci_cliente--, con domicilio en --direccion_cliente--, --ciudad_cliente--, --departamento_cliente--, teléfono --telefono_cliente--, en adelante denominado "EL CLIENTE", y LA EMPRESA, se celebra el presente contrato en fecha --fecha_contrato-- bajo las siguientes cláusulas:

PRIMERA: El cliente --nombre_completo-- solicita los servicios de [DESCRIPCIÓN DE SERVICIOS].

SEGUNDA: El cliente se compromete a cumplir con los términos y condiciones establecidos en el presente contrato.

TERCERA: La vigencia del presente contrato es de [PLAZO] a partir de la fecha de firma.

CUARTA: Cualquier modificación al presente contrato deberá ser realizada por escrito y firmada por ambas partes.

En prueba de conformidad se firma el presente contrato en dos ejemplares de un mismo tenor y a un solo efecto, en fecha --fecha_actual--.


_____________________                    _____________________
    EL CLIENTE                              LA EMPRESA
--nombre_completo--
CI: --ci_cliente--',
  1
);

-- Plantilla de ejemplo: Contrato de Arrendamiento
INSERT INTO public.plantillas_contratos (nombre_plantilla, descripcion, contenido_plantilla, user_id)
VALUES (
  'Contrato de Arrendamiento',
  'Plantilla para contratos de alquiler',
  'CONTRATO DE ARRENDAMIENTO

Conste por el presente documento el CONTRATO DE ARRENDAMIENTO que celebran:

ARRENDATARIO: --nombre_completo--, identificado con CI N° --ci_cliente--, con domicilio en --direccion_cliente--, --ciudad_cliente--, --departamento_cliente--, teléfono --telefono_cliente--.

ARRENDADOR: [NOMBRE DEL ARRENDADOR], identificado con CI N° [CI DEL ARRENDADOR], en adelante "EL ARRENDADOR".

PRIMERA: OBJETO DEL CONTRATO
El ARRENDADOR da en arrendamiento al ARRENDATARIO el inmueble ubicado en [DIRECCIÓN DEL INMUEBLE].

SEGUNDA: PLAZO
El plazo del presente contrato es de [PLAZO] meses, iniciando el --fecha_contrato--.

TERCERA: RENTA
El ARRENDATARIO se obliga a pagar la suma de [MONTO] mensuales.

CUARTA: GARANTÍA
El ARRENDATARIO entrega en este acto la suma de [MONTO] en concepto de garantía.

En señal de conformidad, las partes firman el presente contrato en fecha --fecha_actual--.


_____________________                    _____________________
  EL ARRENDATARIO                         EL ARRENDADOR
--nombre_completo--
CI: --ci_cliente--',
  1
);

-- ============================================================
-- PALABRAS CLAVE DISPONIBLES (DOCUMENTACIÓN)
-- ============================================================

/*
PALABRAS CLAVE DISPONIBLES PARA USAR EN LAS PLANTILLAS:

Datos del Cliente:
--nombre_cliente--         → Nombre del cliente
--apellido_cliente--       → Apellido del cliente
--nombre_completo--        → Nombre completo (nombre + apellido)
--ci_cliente--             → Número de documento (CI)
--telefono_cliente--       → Teléfono del cliente
--direccion_cliente--      → Dirección del cliente
--ciudad_cliente--         → Nombre de la ciudad
--departamento_cliente--   → Nombre del departamento
--fecha_nacimiento--       → Fecha de nacimiento (formato dd/mm/yyyy)
--edad_cliente--           → Edad del cliente en años

Datos del Sistema:
--fecha_actual--           → Fecha actual del sistema (formato dd/mm/yyyy)
--fecha_contrato--         → Fecha del contrato (por defecto fecha actual)
--usuario_sistema--        → Nombre del usuario que genera el contrato

EJEMPLO DE USO:
El cliente --nombre_completo-- con CI --ci_cliente-- firma el contrato en fecha --fecha_actual--.

RESULTADO:
El cliente Juan Pérez con CI 1234567 firma el contrato en fecha 13/11/2025.
*/
