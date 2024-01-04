<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = $_POST;
    echo '<pre>';
    print_r($postData);
    echo '</pre>';
}
?>
