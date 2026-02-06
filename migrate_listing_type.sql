-- Add listing_type and rent_period columns to properties table
ALTER TABLE properties 
    ADD COLUMN IF NOT EXISTS listing_type ENUM('sale', 'rent') DEFAULT 'sale' AFTER type_id,
    ADD COLUMN IF NOT EXISTS rent_period ENUM('monthly', 'yearly', 'daily') DEFAULT NULL AFTER price;

-- Update status ENUM to include 'rented'
ALTER TABLE properties 
    MODIFY COLUMN status ENUM('available', 'sold', 'reserved', 'rented') DEFAULT 'available';
