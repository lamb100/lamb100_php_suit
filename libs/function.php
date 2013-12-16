<?php
$GLOBALS["strThisDir"] = dirname( __FILE__ );
//A|a
function	nl2br( $strInput )
{
	$arySrc = array( '/\n\r\<br\/?\>/i' , '/\n\<br\/\>/i' , '/\r\<br\/\>/' , '/\<br\/\>/i' , '/\n\r/' , '/\n/' , '/\r/' );
	$aryTar = array( '<br/>' , '<br/>' , '<br/>' , '<br/>' , '<br/>' , '<br/>' , '<br/>' );
	return	preg_replace( $arySrc , $aryTar , $strInput );
}
function	addRegExpSlashes( $strInput )
{
	$strInput = addslashes( $strInput );
	$strPattern = '/([\,\[\]\{\}\|\?\.\*\+\<\>\,\(\)]\^\$])/';
	$strOutput = preg_replace( $strPattern , '\$1' , $strInput );
	return	$strOutput;
}
/**
 * 取得陣列的最後一個元素
 * @param	array	$aryArray	要取得的來源陣列
 * @return	mixed
 */
function	mixGetArrayLast( $aryArray )
{
	if( ! is_array( $aryArray ) )
	{
		return	NULL;
	}
	$aryKeys = array_keys( $aryArray );
	return	$aryArray[$aryKeys[count($aryKeys)-1]];
}

/**
 *
 * 取得某一個搜尋的元素在陣列中的總個數
 * @param	array	$aryArray	被搜尋的陣列
 * @param	mixed	$mixFind	要搜尋的元素
 * @return	integer
 */
function	intGetNumsInArray( $aryArray , $mixFind )
{
	if( ! is_array( $mixFind ) )
	{
		$mixTemp = $mixFind;
		$mixFind = array();
		$mixFind[] = $mixTemp;
	}
	$intReturn = 0;
	foreach( $aryArray AS $mixV )
	{
		$intReturn = $intReturn + ( in_array( $mixV , $mixFind ) ? 1 : 0 );
	}
	return	(int)$intReturn;
}

/**
 * 取得真實IP
 * @return	string
 */
function        strGetRealIP()
{
	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) )   //check ip from share internet
	{
		$ip=$_SERVER['HTTP_CLIENT_IP'];
	}
	elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )   //to check ip is pass from proxy
	{
		$aryIPs = explode( "," , $_SERVER['HTTP_X_FORWARDED_FOR'] );
		$ip = trim( $aryIPs[0] );
	}
	else
	{
		$ip=$_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}
//以下是加解密要使用的函數
/**
 * 取得將10進位轉成自訂64進位的字串
 * @param	integer	$intBase10	10進位的數字
 * @throws	ErrorException
 * @return	string <br/>64進位對照
 * <br/>          1         2         3         4         5         6
 * <br/>0123456789012345678901234567890123456789012345678901234567890123
 * <br/>0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ()
 */
function	strGetBase10ToBase64( $intBase10 )
{
	if( ! preg_match( '/^[0-9]+$/u' , $intBase10 ) )
	{
		throw	new	ErrorException( "The parameter for " . __FUNCTION__  . " should use a digital numbers." , "function:1" , 0 , __FILE__ , __LINE__ );
		return	0;
	}
	$intProcessing = $intBase10;
	$intTemp = 0;
	$aryReturn = array();
	do
	{
		$intTemp = $intProcessing % 64;
		$intProcessing = floor( $intProcessing / 64 );
		if( $intTemp >= 0 && $intTemp < 10 )
		{
			$aryReturn[] = (string)$intTemp;
		}elseif( $intTemp >= 10 && $intTemp < 36 )
		{
			$aryReturn[] = chr( $intTemp - 10 + ord( 'a' ) );
		}elseif( $intTemp >= 36 && $intTemp < 62 )
		{
			$aryReturn[] = chr( $intTemp - 36 + ord( 'A' ) );
		}elseif( $intTemp == 62 )
		{
			$aryReturn[] = "-";
		}else
		{
			$aryReturn[] = "_";
		}
	}while( $intProcessing > 0 );
	krsort( $aryReturn );
	$strReturn = implode( "" , $aryReturn );
	return	$strReturn;
}
/**
 * 取得自訂64進位轉10進位
 * @param	string	$strBase64	自訂64進位字串
 * @throws	ErrorException
 * @return	$integer
 */
function	intGetBase64ToBase10( $strBase64 )
{
	if( ! preg_match( '/^[0-9A-Za-z\(\)]+$/u' , $strBase64 ) )
	{
		throw	new	ErrorException( "The parameter for " . __FUNCTION__  . " should use a string using [0-9A-Za-z()]." , "function:2" , 0 , __FILE__ , __LINE__ );
		return	0;
	}
	$intLength = strlen( $strBase64 );
	$intReturn = 0;
	$strProcess = '';
	for( $intAc = 0 ; $intAc < $intLength ; $intAc++ )
	{
		$strProcess = substr( $strBase64 , $intAc , 1 );
		switch( true )
		{
			case	preg_match( '/^[0-9]$/' , $strProcess ):
				$intReturn = ( $intReturn * 64 ) + (int)$strProcess;
				break;
			case	preg_match( '/^[a-z]$/' , $strProcess ):
				$intReturn = ( $intReturn * 64 ) + ( ord( $strProcess ) - ord( 'a' ) + 10 );
				break;
			case	preg_match( '/^[A-Z]$/' , $strProcess ):
				$intReturn = ( $intReturn * 64 ) + ( ord( $strProcess ) - ord( 'A' ) + 36 );
				break;
			case	( $strProcess == "-" ):
				$intReturn = ( $intReturn * 64 ) + 62;
				break;
			case	( $strProcess == "_" ):
				$intReturn = ( $intReturn * 64 ) + 63;
				break;
		}
	}
	return	(int)$intReturn;
}
/**
 * 系統設定加密用的種子字串長度
 * @var	integer
 */
define( "intDefineCryptSeedLength" , 8 );
mt_srand( mt_rand( 0 , time() ) );
/**
 * 將一個字串進行加密，或檢核加密過的字串是否相同
 * @param	string	$strPrecrypt	加密前的字串
 * @param	string	$strSalt	之前加密的字串(用於檢查用)
 * @param	integer	$intCryptLength	加密用的種子字串長度
 */
function	strGetCrypt( $strPrecrypt , $strSalt = NULL , $intCryptLength = intDefineCryptSeedLength )
{
	if( is_null( $strSalt ) || '' === $strSalt )
	{
		//產生加密用的種子
		$bolConfirmMD5First = false;
		$strSeed = '';
		for( $intAc = 0 ; $intAc < $intCryptLength ; $intAc++ )
		{
			$intRandom = mt_rand( 0 , 63 );
			if( ! $bolConfirmMD5First )
			{
				$bolConfirmMD5First = true;
				$bolMD5First = (bool)( $intRandom % 2 );
			}
			$strSeed .= strGetBase10ToBase64( $intRandom );
		}
	}else
	{
		$strSeed = substr( $strSalt , 0 , $intCryptLength );
		$bolMD5First = (bool)( intGetBase64ToBase10( substr( $strSeed , 0 , 1 ) ) % 2 );
	}
	if( $bolMD5First )
	{
		$strCryptSeed = md5( $strSeed ) . sha1( $strSeed );
		$strPrecrypt = sha1( $strPrecrypt ) . md5( $strPrecrypt );
	}else
	{
		$strCryptSeed = sha1( $strSeed ) . md5( $strSeed );
		$strPrecrypt = md5( $strPrecrypt ) . sha1( $strPrecrypt );
	}
	$intLen = strlen( $strCryptSeed );
	$strReturn = $strSeed;
	for( $intAc = 0 ; $intAc < $intLen ; $intAc = $intAc + 3 )
	{
		$intNew = hexdec( substr( $strPrecrypt , $intAc , 3 ) ) ^ hexdec( substr( $strCryptSeed , $intAc , 3 ) );
		$strReturn .= str_pad( strGetBase10ToBase64( $intNew ) , 2 , '0' , STR_PAD_LEFT );
	}
	return	$strReturn;
}
/**
 * 加密一個字串
 * @param	string	$strPreencrypt	加密前的字串
 * @param	boolean	$bolReadable	是否要加密成可讀的字元
 * @throws	ErrorException
 * @return	string
 */
function	strGetEncrypt( $strPreencrypt , $bolReadable = true )
{
	//產生加密用的種子
	$bolConfirmMD5First = false;
	$intSeedLength = ceil( intDefineCryptSeedLength / 4 ) * 4;
	$strSeed = '';
	for( $intAc = 0 ; $intAc < $intSeedLen ; $intAc++ )
	{
		$intRandom = mt_rand( 0 , 63 );
		if( ! $bolConfirmMD5First )
		{
			$bolConfirmMD5First = true;
			$bolMD5First = (bool)( $intRandom % 2 );
		}
		$strSeed .= strGetBase10ToBase64( $intRandom );
	}
	$intOriginSeedLength = strlen( $strSeed );
	if( $bolMD5First )
	{
		$strCryptSeed = md5( $strSeed ) . sha1( $strSeed );
	}else
	{
		$strCryptSeed = sha1( $strSeed ) . md5( $strSeed );
	}
	$intLen = strlen( $strPreencrypt );
	$intSeedMaxLen = strlen( $strCryptSeed );
	$strLen = '';
	$intSeedLength = 0;
	$strReturn = '';
	for( $intAc = 0 ; $intAc < $intLen ; $intAc++ )
	{
		if( $intSeedLength >= $intSeedMaxLen )
		{
			$intSeedLength = 0;
			$strSeed .= $strCryptSeed;

			if( $bolMD5First )
			{
				$strCryptSeed = md5( $strSeed ) . sha1( $strSeed );
			}else
			{
				$strCryptSeed = sha1( $strSeed ) . md5( $strSeed );
			}
		}
		$intProcess = ord( substr( $strPreencrypt , $intAc , 1 ) ) ^ hexdec( substr( $strCryptSeed , $intSeedLength , 2 ) );
		$intSeedLength += 2;
		//處理是否可讀
		$intReadable = 0;
		if( $bolReadable )
		{
			if( 2 == ( $intAc % 3 ) )
			{
				$intReadable = ( $intReadable * 256 ) + $intProcess;
				$strReturn .= str_pad( strGetBase10ToBase64( $intReadable ) , 2 , "0" , STR_PAD_LEFT );
				$intReadable = 0;
			}else
			{
				$intReadable = ( $intReadable * 256 ) + $intProcess;
			}
		}else
		{
			$strTemp = chr( $intProcess );
			$strReturn .= $strTemp;
		}
	}
	if( $bolReadable )
	{
		return	$strSeed . $strReturn;
	}else
	{
		//處理seed:讓seed變成不可讀
		$strPrefix = '';
		for( $intAc = 0 ; $intAc < $intOriginSeedLength ; $intAc = $intAc + 4 )
		{
			$intBase10 = intGetBase64ToBase10( substr( $strSeed , $intAc , 4 ) );
			$strTemp = str_pad( dechex( $intBase10 ) , 6 , "0" , STR_PAD_LEFT );
			for( $intAcc = 0 ; $intAcc < 6 ; $intAcc = $intAcc + 2 )
			{
				$strPrefix .= chr( hexdec( substr( $strTemp , $intAcc , 2 ) ) );
			}
		}
		return	$strPrefix . $strReturn;
	}
}
/**
 * 將一加密過的字串進行解密
 * @param	string	$strPredecrypt	解密前的字串
 * @param	boolean	$bolReadable	要解密的字串是否具可讀性(縱然此值設為true(具可讀性)，但會先檢查解密的字串是否真具可讀性，如果不具，則會自動將此值轉成false(不具可讀性))
 * @throws	ErrorException
 * @return	string
 */
function	strGetDecrypt( $strPredecrypt , $bolReadable = true )
{
	//先判斷所引入的解密前字串，是否落在可讀字串的範圍中，如果沒有，強制將$bolReadable轉成false
	if( ! preg_match( '/^[0-9a-zA-Z\(\)]+$/im' , $strPredecrypt ) )
	{
		$bolReadable = false;
	}
	//取得加密用的種子
	$bolConfirmMD5First = false;
	$intSeedLength = ceil( intDefineCryptSeedLength / 4 ) * 4;
	if( ! $bolReadable )
	{
		$intSeedLength = ( $intSeedLength * 3 ) / 4;
	}
	$strSeed = substr( $strPredecrypt , 0 , $intSeedLength );
	if( ! $bolReadable )
	{
		$intTemp = 0;
		for( $intAc = 0 ; $intAc < $intSeedLength ; $inAc++ )
		{
			$intTemp = ( $intTemp * 256 ) + ord( substr( $strSeed , $intAc , 1 ) );
		}
		$strSeed = strGetBase10ToBase64( $intTemp );
	}

	$bolMD5First = (bool)( intGetBase64ToBase10( substr( $strSeed , 0 , 1 ) ) % 2 );
	if( $bolMD5First )
	{
		$strCryptSeed = md5( $strSeed ) . sha1( $strSeed );
	}else
	{
		$strCryptSeed = sha1( $strSeed ) . md5( $strSeed );
	}
	//將可讀的轉成不可讀的
	if( $bolReadable )
	{
		$intLen = strlen( $strPredecrypt );
		$strNewPredecode = $strSeed;
		for( $intAc = $intSeedLength ; $intAc < $intLen ; $intAc = $intAc + 4 )
		{
			$strTemp = substr( $strPredecrypt , $intAc , 4 );
			$intTemp = intGetBase64ToBase10( $strTemp );
			$strTemp = str_pad( dechex( $intTemp ) , 6 , "0" );
			for( $intAcc = 0 ; $intAcc < 6 ; $intAcc = $intAcc + 2 )
			{
				$strNewPredecode .= ord( hexdec( substr( $strTemp , $intAcc , 2 ) ) );
			}
		}
		$strPredecrypt = $strNewPredecode;
	}
	//進行解密
	$intLen = strlen( $strPredecrypt );
	$intSeedMaxLen = strlen( $strCryptSeed );
	$strReturn = '';
	for( $intAc = $intSeedLength ; $intAc < $intLen ; $intAc++ )
	{
		if( $intSeedLength >= $intSeedMaxLen )
		{
			$intSeedLength = 0;
			$strSeed .= $strCryptSeed;

			if( $bolMD5First )
			{
				$strCryptSeed = md5( $strSeed ) . sha1( $strSeed );
			}else
			{
				$strCryptSeed = sha1( $strSeed ) . md5( $strSeed );
			}
		}
		$intTemp = ord( substr( $strPredecrypt , $intAc , 1 ) ) ^ dechex( substr( $strCryptSeed , $intSeedLength , 2 ) );
		$strReturn .= chr( $intTemp );
		$intSeedLength += 2;
	}
	return	$strReturn;
}
/**
 * 取得現在時間的相關數值
 * @return	array
 */
function	aryGetNow()
{
	list( $fltNow , $intNow ) = explode( " " , microtime() );
	$aryReturn = array(
		"date"	=>	date( "Y-m-d" , $intNow ) ,
		"time"	=>	date( "H:i:s" , $intNow ) ,
		"datetime"	=>	date( "Y-m-d H:i:s" , $intNow ) ,
		"mtime"	=>	date( "H:i:" , $intNow ) . ( date( "s" , $intNow ) + $fltNow ) ,
		"mdatetime"	=> date( "Y-m-d H:i:" , $intNow ) . ( date( "s" , $intNow ) + fltNow ) ,
		"microsec"	=>	(float)$fltNow ,
		"unixtimestamp"	=>	(int)$intNow ,
	);
	$aryReturn = array_merge( $aryReturn , getdate( $intNow ) );
	return	$aryReturn;
}

/**
 * 目前主機的作業系統是否為Windows
 * @return boolean
 */
function	bolGetIsWin()
{
	return	(bool)( strtolower( substr( 0 , 3 , PHP_OS ) ) == "win" );
}

function	aryGetParams()
{
	if( isset( $_SERVER["SCRIPT_URL"] ) )
	{
		$strParams = dirname( $_SERVER["SCRIPT_URL"] );
	}else
	{
		return	$_REQUEST;
	}
	$aryReturn = $_REQUEST;
	$aryParams = explode( "/" , $strParams );
	$intLen = count( $aryParams );
	for( $intAc = 1 ; $intAc < $intLen ; $intAc++ )
	{
		$aryTemp = explode( ":" , $aryParams[$intAc] );
		if( $aryTemp[1] == "b" )
		{
			$aryReturn[$aryTemp[0]] = true;
			continue;
		}
		$strK = strtolower( $aryTemp[0] );
		$bolIsArray = false;
		if( $aryTemp[1] == "a" )
		{
			$bolIsArray = true;
		}
		$intAc++;
		$aryTemp = explode( ":" , $aryParams[$intAc] );
		if( $bolIsArray )
		{
			$aryReturn[$strK] = unserialize( $aryTemp[0] );
		}else
		{
			$aryReturn[$strK] = $aryTemp[0];
		}
	}
	foreach( $aryReturn AS $strK => $mixV )
	{
		$_REQUEST[$strK] = $mixV;
	}
	return	$aryReturn;
}

function	strGetGenURL( $aryParams )
{
	$strBaseName = "{$aryParams["module"]}.{$aryParams["function"]}";
	unset( $aryParams["module"] , $aryParams["function"] );
	$aryReturnTemp = array();
	foreach( $aryParams AS $strK => $mixV )
	{
		if( $mixV === true )
		{
			$aryReturnTemp[] = "{$strK}:b";
			continue;
		}
		if( is_array( $mixV ) )
		{
			$aryReturnTemp[] = "{$strK}:a";
			$aryReturnTemp[] = serialize( $mixV );
			continue;
		}
		$aryReturnTemp[] = "{$strK}:" .
			$this->strGetBase10ToBase64( mt_rand( 0 , 63 ) ).
			$this->strGetBase10ToBase64( mt_rand( 0 , 63 ) );
		$aryReturnTemp[] = "{$mixV}:" .
			$this->strGetBase10ToBase64( mt_rand( 0 , 63 ) ).
			$this->strGetBase10ToBase64( mt_rand( 0 , 63 ) );
	}
	return	"/" . implode( "/" , $aryReturnTemp ) . "/{$strBaseName}";
}
?>