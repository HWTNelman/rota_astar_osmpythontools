-- OPCIONAL e NAO destrutivo. Rode no phpMyAdmin (banco log_baa) SOMENTE se a sua tabela baa_rota
-- for a versao simplificada (so id_rota e nome_rota), como em log_baa_struct.sql.
-- Se o seu banco ja tem as colunas rot_*, nada sera alterado (IF NOT EXISTS; requer MariaDB/XAMPP).
ALTER TABLE `baa_rota`
  ADD COLUMN IF NOT EXISTS `rot_origem_nome` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_destino_nome` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_distancia_km` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_tempo_estimado_min` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_custo_estimado_combustivel` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_custo_pedagios` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `rot_ia_analise` text DEFAULT NULL;
