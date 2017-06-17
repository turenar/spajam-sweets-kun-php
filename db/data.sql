BEGIN;

INSERT INTO user (user_id)
VALUES (1), (2), (3), (4), (5);

INSERT INTO authentication (user_id, email, password, token)
VALUES
	(1, 'hoge@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm', 'abcdef123456789'),
	(2, 'hoge+1@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm',
	 '01ba4719c80b6fe911b091a7c05124b64eeece964e09c058ef8f9805daca546b'),
	(3, 'hoge+2@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm',
	 'bbc9d7c7640f72a3243de927fecfa76b396fe0f9e75ba1ad5ba28cc8c6e1446c'),
	(4, 'hoge+3@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm',
	 '2e0390eb024a52963db7b95e84a9c2b12c004054a7bad9a97ec0c7c89d4681d2'),
	(5, 'hoge+4@example.com', '$2y$10$ulqpqhnysOSiV1CiFlFMXeSC2vl4YzT7wNUtUAYmPF/GqGxuZSyAm',
	 'e712aff3705ac314b9a890e0ec208faa20054eee514d86ab913d768f94e01279');

INSERT INTO shop (shop_id, name, open_time, close_time, address, latitude, longitude, geom_hash)
VALUES
	(1, 'おいしいケーキ デ・リ・シャス', '10:00:00', '20:00:00', '東京都百代田区百代田1-1-1', 35.697372, 139.750131, '133002112310202210'),
	(2, 'mystery of crepe', NULL, NULL, '東京都新宿区西新宿4-33-4', 35.686451, 139.688969, '1330021123012303 12'),
	(3, 'Liebesknochen', '11:00:00', '13:30:00', '東京都新宿区西新宿3-11-20', 35.685732, 139.689006, '133002112301230330'),
	(4, '和菓子総本家', '08:00:00', '18:00:00', '東京都新宿区西新宿4-34-10', 35.685742, 139.688497, '133002112301230330'),
	(5, 'スイーツパラリシス', '09:00:00', '24:00:00', '東京都新宿区西新宿4-34-26', 35.686734, 139.68819, '133002112301230303'),
	(6, '百合華 東京都庁店', NULL, NULL, '東京都新宿区西新宿2-8-1', 35.689634, 139.692101, '133002112301231022');

INSERT INTO review (shop_id, user_id, rating, review_text, sweet_type, latitude, longitude, geom_hash)
VALUES
	(1, 1, 4, 'これはレビューです。これはレビューです。これはレビューです。これはレビューです。これはレビューです。', 1, 35.697365, 139.750128, '133002112310202210');

COMMIT;
