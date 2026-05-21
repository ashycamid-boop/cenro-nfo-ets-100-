-- Migration: add thickness, width, length and numeric volume to spot_report_items
ALTER TABLE `spot_report_items`
  ADD COLUMN `thickness_in` DECIMAL(10,3) NULL AFTER `quantity`,
  ADD COLUMN `width_in` DECIMAL(10,3) NULL AFTER `thickness_in`,
  ADD COLUMN `length_ft` DECIMAL(10,3) NULL AFTER `width_in`,
  ADD COLUMN `volume_bdft` DECIMAL(15,3) NULL AFTER `length_ft`;

-- Optional: keep the old textual `volume` column for compatibility (already exists).
-- You may want to migrate existing textual volumes into `volume_bdft` where possible.

-- Example to migrate simple numeric values from `volume` into `volume_bdft` when `volume` contains only numeric characters:
-- UPDATE `spot_report_items`
-- SET `volume_bdft` = CAST(`volume` AS DECIMAL(15,3))
-- WHERE `volume` REGEXP '^[0-9]+(\\.[0-9]+)?$';

-- Add indexes for faster queries on dimensions if needed
ALTER TABLE `spot_report_items`
  ADD INDEX idx_sri_thickness (`thickness_in`),
  ADD INDEX idx_sri_width (`width_in`),
  ADD INDEX idx_sri_length (`length_ft`);
