BEGIN;

INSERT INTO user (user_id)
VALUES (1);

INSERT INTO authentication (user_id, email, password, token)
VALUES (1, 'hoge@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm', 'abcdef123456789');

INSERT INTO shop (shop_id, name, open_time, close_time, address, latitude, longitude, geom_hash)
VALUES
	(1, 'おいしいケーキ デ・リ・シャス', '10:00:00', '20:00:00', '東京都百代田区百代田1-1-1', 35.697372, 139.750131, '133002112310202210');

INSERT INTO review (shop_id, user_id, rating, review_text, sweet_type, latitude, longitude, geom_hash)
VALUES
	(1, 1, 4, 'これはレビューです。これはレビューです。これはレビューです。これはレビューです。これはレビューです。', 1, 35.697365, 139.750128, '133002112310202210');

COMMIT;
