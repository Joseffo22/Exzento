-- Limpia descripciones contaminadas con "Recibida manualmente…"
-- y migra esa metadata a columnas estructuradas (si ya corriste actualizar_tabla_ticket_facturas.sql).
--
-- 1) Asegura columnas de estado (idempotente si ya existen — comentar si falla por duplicado):
-- ALTER TABLE ticket ADD COLUMN estado_factura ENUM('pendiente', 'facturada', 'recibida_manual') DEFAULT 'pendiente';
-- ALTER TABLE ticket ADD COLUMN nota_factura TEXT NULL;
-- ALTER TABLE ticket ADD COLUMN fecha_recepcion_manual DATETIME NULL;
-- ALTER TABLE ticket ADD COLUMN canal_recepcion VARCHAR(50) NULL;

-- 2) Marcar como recibida_manual los que aún traen la metadata en descripción
UPDATE ticket
SET estado_factura = 'recibida_manual',
    nota_factura = descripcion,
    canal_recepcion = CASE
        WHEN descripcion LIKE '%por: Correo%' THEN 'Correo electrónico'
        WHEN descripcion LIKE '%por: WhatsApp%' THEN 'WhatsApp'
        WHEN descripcion LIKE '%por: Físicamente%' THEN 'Físicamente'
        ELSE 'Otro'
    END,
    fecha_recepcion_manual = COALESCE(fecha_recepcion_manual, NOW())
WHERE descripcion LIKE '%Recibida manualmente%'
  AND (estado_factura IS NULL OR estado_factura = 'pendiente');

-- 3) Dejar solo el nombre del comercio en descripción
UPDATE ticket
SET descripcion = TRIM(
    REGEXP_REPLACE(
        descripcion,
        '[[:space:]]*[-–—]?[[:space:]]*Recibida manualmente el[[:space:]].*$',
        ''
    )
)
WHERE descripcion LIKE '%Recibida manualmente%';
