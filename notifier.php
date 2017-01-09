#!/usr/local/bin/php
<?php

error_reporting( E_ALL );

die( 'hapus' ); // hapus




// Akun-akun yang akan dicek.
// Parameter terakhir adalah interval pengecekan agar independen dari cron.
// File ini bisa dipanggil tiap 5-10 menit sekali lewat cron.

$accounts = array(

    array( 'BCA', 'username1', 'password1', 'email1@domain', ( 60 * 20 ) ), // tiap 20 menit
    array( 'BCA', 'username2', 'password2', 'email2@domain', ( 3600 * 24 ) ), // tiap 24 jam

);

// Saya mendapat tips legal bahwa otomasi login ke klik BCA dibolehkan
// selama dilakukan oleh pemilik akun.
// Saya juga mendapat tips teknis yang menyebutkan agar login dilakukan
// tidak lebh dari 100x per hari.




// Jalan

run( $accounts );




// run() logic

function run( $accounts )
{

    require( 'IbParser.php' );

    $notifier   = new IbParser;
    $datadir    = dirname( __FILE__ ) . '/data';

    if ( !is_dir( $datadir ) )
        mkdir( $datadir );




    // Langkah-langkah untuk setiap akun

    foreach( $accounts as $account )
    {


        // Periksa file data, kalau false langsung lanjut ke akun berikut

        if ( !$balance = checkDataFile( $account, $datadir ) )
            continue;


        // Ambil balance, kalau false langsung lanjut

        if ( !$new_balance = $notifier->getBalance( $account[0], $account[1], $account[2] ) )
            continue;


        $balance = json_decode( $balance )->balance;


        // Update file data walaupun balancenya sama

        updateDataFile( $account, $datadir, $new_balance );


        // Bandingkan balance, kalau sama langsung lanjut

        if ( $balance == $new_balance )
            continue;


        // Ambil transaksi

        $transactions = $notifier->getTransactions( $account[0], $account[1], $account[2] );


        // Kirim email

        notify( $account, $balance, $new_balance, $transactions );

    }

}




function checkDataFile( $account, $datadir )
{

    $datafile = $datadir . '/' . md5( $account[0] . $account[1] );

    if ( !file_exists( $datafile ) )
    {
        touch( $datafile );
        return json_encode( array( 'balance' => 0 ) );
    }

    if ( filemtime( $datafile ) > time() - $account[4] )
        return false;
    else
        return file_get_contents( $datafile );

}




function updateDataFile( $account, $datadir, $new_balance )
{
    $datafile = $datadir . '/' . md5( $account[0] . $account[1] );
    $fh = fopen( $datafile, 'w' );
    fwrite( $fh, json_encode( array( 'balance' => $new_balance ) ) );
    fclose( $fh );
}




function notify( $account, $balance, $new_balance, $transactions )
{

    $difference = $new_balance - $balance;

    $subject    = ( $difference > 0 )? 'Peningkatan ': 'Penurunan ';
    $subject   .= 'Saldo ' . $account[0] . ' sebesar Rp ' . number_format( abs( $difference ), 2 );


     // Ganti dengan email anda (yang boleh dikirim dari server)

    $headers    = 'From: "BCA Notifier"<notifier@randomlog.org>' . "\n";


    $body       = 'Saldo saat ini Rp ' . number_format( ( $new_balance - 10000 ), 2 ); // 10 ribu saldo wajib di BCA
    $body      .= "\n\n" . 'Transaksi:' . "\n";


    if ( $transactions )
    {

        foreach( $transactions as $transaction )
        {

            $drcr   = ( $transaction[2] == 'CR' )? 'CREDIT': 'DEBIT';
            $body  .= "\n" . $transaction[0] . ' === ' . number_format( $transaction[3], 2 ) . ' === ' . $drcr . "\n" . strtolower( str_replace( "\n", ' ', $transaction[1] ) ) . "\n";

        }

    }
    else
    {
        $body  .= 'Gagal mengambil transaksi';
    }

    mail( $account[3], $subject, $body, $headers );


}
