<?php
$conn = mysqli_connect('localhost','root','','system');
if(!$conn){
    die('DB connetion fails'.mysqli_connect_error());
}
mysqli_set_charset($conn,'utf8mb4');