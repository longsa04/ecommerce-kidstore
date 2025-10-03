ALTER TABLE tbl_orders
    ADD COLUMN shipping_address_id INT NULL AFTER user_id,
    ADD KEY shipping_address_id (shipping_address_id);

UPDATE tbl_orders o
INNER JOIN (
    SELECT user_id, MAX(address_id) AS address_id
    FROM tbl_shipping_addresses
    GROUP BY user_id
) latest ON latest.user_id = o.user_id
SET o.shipping_address_id = latest.address_id
WHERE o.shipping_address_id IS NULL;

ALTER TABLE tbl_orders
    ADD CONSTRAINT tbl_orders_ibfk_2 FOREIGN KEY (shipping_address_id)
        REFERENCES tbl_shipping_addresses (address_id)
        ON DELETE SET NULL;
