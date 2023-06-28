DROP PROCEDURE IF EXISTS `multiply`;

CREATE PROCEDURE `multiply` (IN num_one INT, IN num_two INT, OUT result INT)

BEGIN

SET result = num_one * num_two;

END
