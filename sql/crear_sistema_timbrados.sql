-- =====================================================
-- SCRIPT DE MIGRACIÓN: SISTEMA DE TIMBRADOS
-- Fecha: 2025-11-14
-- Descripción: Crea la tabla de timbrados y actualiza
--              la tabla de ventas para soportar timbrados
-- =====================================================

-- 1. Crear tabla de timbrados
CREATE TABLE IF NOT EXISTS timbrados (
    id_timbrado BIGSERIAL PRIMARY KEY,
    id_empresa BIGINT NOT NULL,
    numero_timbrado VARCHAR(50) UNIQUE NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    rango_inicio INTEGER NOT NULL,
    rango_fin INTEGER NOT NULL,
    numero_actual INTEGER DEFAULT 0 NOT NULL,
    establecimiento VARCHAR(3) DEFAULT '001' NOT NULL,
    punto_expedicion VARCHAR(3) DEFAULT '001' NOT NULL,
    estado VARCHAR(20) DEFAULT 'VIGENTE' NOT NULL
        CHECK (estado IN ('VIGENTE', 'VENCIDO', 'AGOTADO', 'CANCELADO')),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign key
    CONSTRAINT fk_timbrados_empresa
        FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa)
        ON DELETE CASCADE,

    -- Constraints
    CONSTRAINT chk_rango_valido
        CHECK (rango_fin > rango_inicio),
    CONSTRAINT chk_fecha_valida
        CHECK (fecha_vencimiento > fecha_inicio),
    CONSTRAINT chk_numero_actual
        CHECK (numero_actual >= 0)
);

-- 2. Crear índices para optimizar búsquedas
CREATE INDEX IF NOT EXISTS idx_timbrados_estado
    ON timbrados(estado);

CREATE INDEX IF NOT EXISTS idx_timbrados_empresa_estado
    ON timbrados(id_empresa, estado);

CREATE INDEX IF NOT EXISTS idx_timbrados_vigencia
    ON timbrados(fecha_vencimiento);

-- 3. Agregar columnas a la tabla ventas (solo si no existen)
DO $$
BEGIN
    -- Agregar id_timbrado si no existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_name = 'ventas' AND column_name = 'id_timbrado'
    ) THEN
        ALTER TABLE ventas
        ADD COLUMN id_timbrado BIGINT;

        -- Agregar foreign key
        ALTER TABLE ventas
        ADD CONSTRAINT fk_ventas_timbrado
            FOREIGN KEY (id_timbrado)
            REFERENCES timbrados(id_timbrado)
            ON DELETE SET NULL;

        -- Agregar índice
        CREATE INDEX idx_ventas_timbrado
            ON ventas(id_timbrado);
    END IF;
END $$;

-- 4. Registrar la migración en la tabla migrations
INSERT INTO migrations (migration, batch)
VALUES ('2025_11_14_000001_create_timbrados_table',
        COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1)
ON CONFLICT DO NOTHING;

-- 5. Crear permisos para el módulo de timbrados
INSERT INTO permissions (name, guard_name, created_at, updated_at)
VALUES
    ('timbrados index', 'web', NOW(), NOW()),
    ('timbrados create', 'web', NOW(), NOW()),
    ('timbrados edit', 'web', NOW(), NOW()),
    ('timbrados destroy', 'web', NOW(), NOW())
ON CONFLICT (name, guard_name) DO NOTHING;

-- 6. Comentarios para documentación
COMMENT ON TABLE timbrados IS 'Almacena los timbrados fiscales autorizados por la SET para facturación';
COMMENT ON COLUMN timbrados.numero_timbrado IS 'Número único de autorización del timbrado';
COMMENT ON COLUMN timbrados.fecha_inicio IS 'Fecha de inicio de vigencia del timbrado';
COMMENT ON COLUMN timbrados.fecha_vencimiento IS 'Fecha de vencimiento del timbrado';
COMMENT ON COLUMN timbrados.rango_inicio IS 'Número inicial del rango de facturas autorizado';
COMMENT ON COLUMN timbrados.rango_fin IS 'Número final del rango de facturas autorizado';
COMMENT ON COLUMN timbrados.numero_actual IS 'Contador de facturas utilizadas del timbrado';
COMMENT ON COLUMN timbrados.estado IS 'Estado del timbrado: VIGENTE, VENCIDO, AGOTADO, CANCELADO';

-- =====================================================
-- VERIFICACIONES
-- =====================================================

-- Verificar que la tabla se creó correctamente
SELECT
    'timbrados' as tabla,
    COUNT(*) as registros_existentes,
    CASE
        WHEN COUNT(*) >= 0 THEN 'Tabla creada exitosamente ✓'
        ELSE 'ERROR'
    END as estado
FROM timbrados;

-- Verificar columnas agregadas a ventas
SELECT
    column_name,
    data_type,
    is_nullable
FROM information_schema.columns
WHERE table_name = 'ventas'
    AND column_name = 'id_timbrado'
ORDER BY column_name;

-- Verificar permisos creados
SELECT name, guard_name, created_at
FROM permissions
WHERE name LIKE 'timbrados%'
ORDER BY name;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================
