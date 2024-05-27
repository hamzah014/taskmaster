<?php
 
/*
	A simple example demonstrate thumbnail creation.
*/ 
 
/* Create the Imagick object */
$im = new Imagick();
 
/* Read the image file */
$im->readImage( 'C:\laragon\www\Asive\public\document.pdf' );
 
/* Thumbnail the image ( width 100, preserve dimensions ) */
$im->thumbnailImage( 100, null );
 
/* Write the thumbail to disk */
$im->writeImage( 'th_test.png' );
 
/* Free resources associated to the Imagick object */
$im->destroy();
 
?>