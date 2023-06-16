CREATE PROCEDURE `multiply` (IN num_one INT, IN num_two INT, OUT result OUT)

BEGIN

SET result = num_one * num_two;

END