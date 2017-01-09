<?php




class IbParser {




    function __construct() {
        $this->conf['ip']       = $_SERVER['REMOTE_ADDR'];
        $this->conf['time']     = time();
        $this->conf['path']     = dirname( __FILE__ );
    }




    function instantiate( $bank ) {
        $class = $bank . 'Parser';
        $this->bank = new $class( $this->conf ) or trigger_error( 'Undefined parser: ' . $class, E_USER_ERROR );
    }




    function getBalance( $bank, $username, $password ) {

        $this->instantiate( $bank );
        $this->bank->login( $username, $password );
        $balance = $this->bank->getBalance();
        $this->bank->logout();
        return $balance;

    }




    function getTransactions( $bank, $username, $password ) {

        $this->instantiate( $bank );
        $this->bank->login( $username, $password );
        $transactions = $this->bank->getTransactions();
        $this->bank->logout();
        return $transactions;

    }

}




class BCAParser {

    function __construct( $conf ) {
        $this->conf = $conf;
        $start      = strtotime('-1 Day', $this->conf['time']);
        $this->post_time['end']['y'] = date('Y', $this->conf['time']);
        $this->post_time['end']['m'] = date('m', $this->conf['time']);
        $this->post_time['end']['d'] = date('d', $this->conf['time']);
        $this->post_time['start']['y'] = date( 'Y', $start);
        $this->post_time['start']['m'] = date( 'm', $start);
        $this->post_time['start']['d'] = date( 'd', $start);
    }



    function curlexec() {

        curl_setopt( $this->ch, CURLOPT_RETURNTRANSFER, 1 );
        return curl_exec( $this->ch );
    }




    function login( $username, $password ) {

        $this->ch = curl_init();

        curl_setopt( $this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; U; Android 2.3.7; en-us; Nexus One Build/GRK39F) AppleWebKit/533.1 (KHTML, like Gecko) Version/4.0 Mobile Safari/533.1' );
        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/login.jsp' );
        curl_setopt( $this->ch, CURLOPT_COOKIEFILE, $this->conf['path'] . '/cookie' );
        curl_setopt( $this->ch, CURLOPT_COOKIEJAR, $this->conf['path'] . '/cookiejar' );

        $this->curlexec();

        $params = implode( '&', array( 'value(user_id)=' . $username, 'value(pswd)=' . $password, 'value(Submit)=LOGIN', 'value(actions)=login', 'value(user_ip)=' . $this->conf['ip'], 'user_ip=' . $this->conf['ip'], 'value(mobile)=true', 'mobile=true' ) );

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/authentication.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/login.jsp' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );

        $this->curlexec();

    }




    function logout() {

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/authentication.do?value(actions)=logout' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do?value(actions)=menu' );
        $this->curlexec();
        return curl_close( $this->ch );
    }




    function getBalance() {

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do' );

        $this->curlexec();

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/balanceinquiry.do' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu' );

        $src = $this->curlexec();

        $parse = explode( "<td align='right'><font size='1' color='#0000a7'><b>", $src );

        if ( empty( $parse[1] ) )
            return false;

        $parse = explode( '</td>', $parse[1] );

        if ( empty( $parse[0] ) )
            return false;

        $parse = str_replace( ',', '', $parse[0] );

        return ( is_numeric( $parse ) )? $parse: false;

    }




    function getTransactions() {

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/authentication.do' );

        $this->curlexec();

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=menu' );

        $this->curlexec();

        $params = implode( '&', array( 'r1=1', 'value(D1)=0', 'value(startDt)=' . $this->post_time['start']['d'], 'value(startMt)=' . $this->post_time['start']['m'], 'value(startYr)=' . $this->post_time['start']['y'],'value(endDt)=' . $this->post_time['end']['d'], 'value(endMt)=' . $this->post_time['end']['m'], 'value(endYr)=' . $this->post_time['end']['y'] ) );

        curl_setopt( $this->ch, CURLOPT_URL, 'https://m.klikbca.com/accountstmt.do?value(actions)=acctstmtview' );
        curl_setopt( $this->ch, CURLOPT_REFERER, 'https://m.klikbca.com/accountstmt.do?value(actions)=acct_stmt' );
        curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params );
        curl_setopt( $this->ch, CURLOPT_POST, 1 );

        $src = $this->curlexec();

        $parse = explode( '<table width="100%" class="blue">', $src );

        if ( empty( $parse[1] ) )
            return false;

        $parse = explode( '</table>', $parse[1] );
        $parse = explode( '<tr', $parse[0] );

        $rows = array();

        foreach( $parse as $val )
            if ( substr( $val, 0, 8 ) == ' bgcolor' )
                $rows[] = $val;

        foreach( $rows as $key => $val )
        {
            $rows[$key]     = explode( '</td>', $val );
            $rows[$key][0]  = substr( $rows[$key][0], -5 );
            if ( stristr( $rows[$key][0], 'pend' ) )
                $rows[$key][0] = 'PEND';
            $detail         = explode( "<td valign='top'>", $rows[$key][1] );
            $rows[$key][2]  = $detail[1];
            $rows[$key][1]  = explode( '<br>', $detail[0] );
            $rows[$key][3]  = str_replace( ',', '', $rows[$key][1][count($rows[$key][1])-1] );
            unset( $rows[$key][1][count($rows[$key][1])-1] );
            foreach( $rows[$key][1] as $k => $v )
                $rows[$key][1][$k] = trim( strip_tags( $v ) );
            $rows[$key][1] = implode( " ", $rows[$key][1] );
        }

        return ( !empty( $rows ) )? $rows: false;

    }

}
