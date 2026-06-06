-- Migration: expand equipment.status enum to match UI statuses
ALTER TABLE `equipment`
  MODIFY COLUMN `status` ENUM('Available','In Use','Under Maintenance','Damaged','Out of Service','Disposed')
  NOT NULL DEFAULT 'In Use';

-- Cleanup: replace blank status values created by previous enum mismatch
UPDATE `equipment`
SET `status` = 'Out of Service'
WHERE `status` IS NULL OR TRIM(`status`) = '';
