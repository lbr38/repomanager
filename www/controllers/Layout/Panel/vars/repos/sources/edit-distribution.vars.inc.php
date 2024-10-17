<?php
/**
 *  Check that Id and distribution params have been sent
 */
if (empty($item['id'])) {
    throw new Exception('Missing repository Id');
}
if (empty($item['distribution'])) {
    throw new Exception('Missing distribution');
}

