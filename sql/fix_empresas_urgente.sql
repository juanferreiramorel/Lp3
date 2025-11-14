-- =====================================================
-- FIX URGENTE: Agregar columnas faltantes a tabla empresas
-- Ejecuta este script AHORA para solucionar el error
-- =====================================================

-- 1. Agregar columna updated_at si no existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT FROM information_schema.columns
        WHERE table_name = 'empresas' AND column_name = 'updated_at'
    ) THEN
        ALTER TABLE empresas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
        RAISE NOTICE 'Columna updated_at agregada a empresas';
    ELSE
        RAISE NOTICE 'La columna updated_at ya existe';
    END IF;
END $$;

-- 2. Agregar columna created_at si no existe
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT FROM information_schema.columns
        WHERE table_name = 'empresas' AND column_name = 'created_at'
    ) THEN
        ALTER TABLE empresas ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
        RAISE NOTICE 'Columna created_at agregada a empresas';
    ELSE
        RAISE NOTICE 'La columna created_at ya existe';
    END IF;
END $$;

-- 3. Verificar todas las columnas necesarias
DO $$
DECLARE
    columnas_faltantes TEXT := '';
BEGIN
    -- Verificar razon_social
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'razon_social') THEN
        ALTER TABLE empresas ADD COLUMN razon_social VARCHAR(255) NOT NULL;
        columnas_faltantes := columnas_faltantes || 'razon_social, ';
    END IF;

    -- Verificar nombre_comercial
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'nombre_comercial') THEN
        ALTER TABLE empresas ADD COLUMN nombre_comercial VARCHAR(255);
        columnas_faltantes := columnas_faltantes || 'nombre_comercial, ';
    END IF;

    -- Verificar ruc
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'ruc') THEN
        ALTER TABLE empresas ADD COLUMN ruc VARCHAR(50) NOT NULL;
        columnas_faltantes := columnas_faltantes || 'ruc, ';
    END IF;

    -- Verificar direccion
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'direccion') THEN
        ALTER TABLE empresas ADD COLUMN direccion TEXT;
        columnas_faltantes := columnas_faltantes || 'direccion, ';
    END IF;

    -- Verificar telefono
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'telefono') THEN
        ALTER TABLE empresas ADD COLUMN telefono VARCHAR(40);
        columnas_faltantes := columnas_faltantes || 'telefono, ';
    END IF;

    -- Verificar email
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'email') THEN
        ALTER TABLE empresas ADD COLUMN email VARCHAR(100);
        columnas_faltantes := columnas_faltantes || 'email, ';
    END IF;

    -- Verificar logo
    IF NOT EXISTS (SELECT FROM information_schema.columns WHERE table_name = 'empresas' AND column_name = 'logo') THEN
        ALTER TABLE empresas ADD COLUMN logo VARCHAR(255);
        columnas_faltantes := columnas_faltantes || 'logo, ';
    END IF;

    IF columnas_faltantes != '' THEN
        RAISE NOTICE 'Columnas agregadas: %', columnas_faltantes;
    ELSE
        RAISE NOTICE 'Todas las columnas ya existen';
    END IF;
END $$;

-- 4. Mostrar estructura actual de la tabla
SELECT
    column_name,
    data_type,
    character_maximum_length,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'empresas'
ORDER BY ordinal_position;

-- 5. Mensaje final
DO $$
BEGIN
    RAISE NOTICE '==============================================';
    RAISE NOTICE 'FIX COMPLETADO';
    RAISE NOTICE 'Ahora puedes crear empresas sin problemas';
    RAISE NOTICE '==============================================';
END $$;
