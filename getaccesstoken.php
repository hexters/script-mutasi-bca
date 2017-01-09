<?php
  $token = '';
  if($_SERVER['REQUEST_METHOD'] == 'POST'):
    $data['username'] = $_POST['username'];
    $data['password'] = $_POST['password'];
    $data['bank'] = $_POST['bank'];
    $token = base64_encode(serialize($data));
  endif;
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>ACCESS TOKEN</title>
  </head>
  <body>

    <form class="" action="" method="post">
      <p>Silakan masukan username & password akun click BCA anda. Mutasi diambil dari transaksi 2 jam terakhir</p>
      <table>
        <tr>
            <td>USERNAME</td>
          <td> : <input type="text" name="username" required></td>
        </tr>
        <tr>
          <td>PASSWORD</td>
          <td> : <input type="password" name="password" required></td>
        </tr>
        <tr>
          <td>BANK</td>
          <td> :
            <select class="" name="bank">
              <option value="BCA">BCA</option>
            </select>
          </td>
        </tr>

        <tr>
          <td colspan="2"><hr /></td>
        </tr>

        <tr>
          <td>X-ACCESS-TOKEN</td>
          <td> : <input type="text" readonly value="<?php echo $token; ?>"></td>
        </tr>

        <tr>
          <td></td>
          <td align="right"><button type="submit">GET TOKEN</button></td>
        </tr>
      </table>
    </form>

    <p>
      <code>
        KET : Riwayat Mutasi <br />
        URL : /getmutasi<br />
        METHOD : POST<br />
        HEADER : X-ACCESS-TOKEN
        <hr />
        KET : Cek Saldo <br />
        URL : /getsaldoi<br />
        METHOD : POST<br />
        HEADER : X-ACCESS-TOKEN
      </code>
    </p>
  </body>
</html>
