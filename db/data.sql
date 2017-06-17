BEGIN;

INSERT INTO shop (name, open_time, close_time, address, latitude, longitude, geom_hash)
VALUES
	('おいしいケーキ デ・リ・シャス', '10:00:00', '20:00:00', '東京都百代田区百代田', 35.697372, 139.750131, '13300211231020221');

COMMIT;
