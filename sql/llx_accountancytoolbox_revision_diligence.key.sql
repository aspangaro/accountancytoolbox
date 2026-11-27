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

ALTER TABLE llx_accountancytoolbox_revision_diligence ADD INDEX idx_accountancytoolbox_revision_diligence_entity (entity);
ALTER TABLE llx_accountancytoolbox_revision_diligence ADD INDEX idx_accountancytoolbox_revision_diligence_fk_guide (fk_guide);
ALTER TABLE llx_accountancytoolbox_revision_diligence ADD INDEX idx_accountancytoolbox_revision_diligence_fk_cycle (fk_cycle);
ALTER TABLE llx_accountancytoolbox_revision_diligence ADD UNIQUE INDEX uk_accountancytoolbox_revision_diligence_ref (fk_cycle, ref);
