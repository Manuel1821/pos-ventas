-- =============================================================================
-- Si borraste cash_sessions (u otras tablas) a mano pero el setup cree que
-- "ya migraste", aparece: Table 'cash_sessions' doesn't exist (1146).
--
-- Solución: vaciar el registro de migraciones y volver a ejecutar /setup (SI).
-- Las migraciones están pensadas para ser idempotentes (IF NOT EXISTS, etc.).
-- =============================================================================

TRUNCATE TABLE schema_migrations;

-- Luego en el navegador: /public/setup → escribe SI → Aplicar cambios.
