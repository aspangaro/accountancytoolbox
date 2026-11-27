-- ============================================================================
-- Copyright (C) 2026  Alexandre Spangaro  <alexandre@inovea-conseil.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <https://www.gnu.org/licenses/>.
-- ============================================================================
--
-- Diligence (question de contrôle) attachée à un cycle d'un modèle de guide de révision
--

CREATE TABLE llx_accountancytoolbox_revision_diligence (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  entity integer NOT NULL DEFAULT 1,
  fk_guide integer NOT NULL,
  fk_cycle integer NOT NULL,
  ref varchar(16) NOT NULL,
  label text,
  type_control varchar(20) NOT NULL DEFAULT 'justificatifs',
  accounts_filter text,
  numerator_filter text,
  denominator_filter text,
  applicable_default smallint NOT NULL DEFAULT 1,
  priority smallint NOT NULL DEFAULT 0,
  note text,
  position integer NOT NULL DEFAULT 0,
  active smallint NOT NULL DEFAULT 1,
  fk_user_cre integer DEFAULT NULL,
  fk_user_modif integer DEFAULT NULL,
  datec datetime NOT NULL,
  tms timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=innodb;
