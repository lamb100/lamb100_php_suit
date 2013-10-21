<?php
include( '../conf/set.basic.php' );
include( 'function.php' );
include( "{$_APPF["DIR_3RD_PARTY"] }/adodb/adodb.inc.php" );
include( "{$_APPF["DIR_3RD_PARTY"] }/smarty/Smarty.class.php" );

abstract	class	Core	extends	stdClass
{
	/**
	 * 系統暫存快取
	 * @var	array
	 */
	protected	$Cache = array();
	protected	$Debug = array(
		"sql" => array() ,
		"msg"	=> array() ,
		"timestamp" => array() ,
		"last"	=>	array(
			"sql" => '' ,
			"msg"	=>	'' ,
			"time"	=>	array() ,
			"mem"	=>	0
		) ,
		"start" => array(
			"time"	=>	array() ,
			"mem"	=>	0
		) ,
		"mem"	=>	array() ,
		"flag"	=>	false ,
	);
	protected	$ParamsDefine = array();
	protected	$Request = array();
	protected	$Session = array();
	public	$LastResult = false;
	protected	$DB = array();
	protected	$View = array();
	protected	$_APPF = array();
	protected	$_LANG = array();


	/*Magic Methods*/
	public	function	__construct()
	{
		$this->BeforeConstruct();
	}
	public	function	__destruct()
	{
		$this->BeforeDestruct();
	}
	public	function	__sleep(){}
	public	function	__wakeup(){}
	public	function	__call(){}
	public	function	__callStatic(){}
	public	function	__set(){}
	public	function	__get(){}
	public	function	__isset(){}
	public	function	__unset(){}
	public	function	__toString()
	{
		return	__CLASS__;
	}
	public	function	__invoke(){}
	public	function	__set_state(){}
	public	function	__clone(){}

	/**
	 * 在實體化成物件前的一些準備工作
	 * @return Core
	 */
	protected	function	&BeforeConstruct()
	{
		$this->Debug["start"]["time"] = $this->GetTime();
		$this->Debug["start"]["mem"] = memory_get_usage();
		global	$_APPF;
		$this->_APPF = &$_APPF;
		return	$this;
	}

	/**
	 * 取得各樣時間的數值
	 * @param	Ambigous <integer, array>
	 * @return	array <br/>
	 * second 秒(0-59)<br/>
	 * minutes	分(0-59)<br/>
	 * hours	時(0-23)<br/>
	 * mday	一個月的第幾天<br/>
	 * wday	一週中的第幾天<br/>
	 * mon	一年中第幾月<br/>
	 * year	西元年，四碼<br/>
	 * yday	一年中第幾天<br/>
	 * weekday	今天星期幾文(英文)<br/>
	 * month	幾月(英文字)<br/>
	 * umtime	unix timestamp with microsecond<br/>
	 * utime	unix timestamp without microsecond<br/>
	 * msec		微秒
	 */
	static	protected	function	GetTime( $mixTime = NULL )
	{
		$aryReturn = array();
		if( is_null( $mixTime ) )
		{
			list( $fltTime , $intTime ) = explode( " " , microtime() );
		}elseif( preg_match( '/^[0-9]+(\.[0-9]+)?$/i' , $mixTime ) )
		{
			list( $intTime , $fltTime ) = explode( "." , $mixTime );
			$fltTime = (float)"0.{$fltTime}";
			$intTime = (int)$intTime;
		}elseif( preg_match( '/\.[0-9]+?$/i' , $mixTime ) )
		{
			list( $strTime , $fltTime ) = explode( "." , $mixTime );
			$fltTime = (float)"0.{$fltTime}";
			$intTime = strtotime( $strTime );
		}else
		{
			$fltTime = 0.0;
			$intTime = strtotime( $mixTime );
		}
		$aryReturn = getdate( $intTime );
		$aryReturn["umtime"] = $intTime + $fltTime;
		$aryReturn["utime"] = $aryReturn[0];
		$aryReturn["msec"] = $fltTime;
		unset( $aryReturn[0] );
		return	$aryReturn;
	}

	/**
	 * 設定時間追踪
	 * @return Core
	 */
	protected	function	&SetTimeTrace()
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
			$this->Debug["timestamp"][$aryTime["umtime"]] = $aryTime;
		}
		return	$this;
	}

	protected	function	&SetMemTrace()
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
			$this->Debug["mem"][$aryTime["umtime"]] = memory_get_usage();
		}
		return	$this;
	}

	protected	function	&SetSQLTrace( $strFile , $intLine , $strSQL )
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
		}
		$this->Debug["sql"][$strFile][$intLine][$aryTime["umtime"]] = $strSQL;
		return	$this;
	}

	protected	function	&SetMessage( $strFile , $intLine , $strMessage )
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
			$this->Debug["msg"][$strFile][$intLine][$aryTime["umtime"]] = $strSQL;
		}
		return	$this;
	}

	public	function	&InitDB( $strDBType = NULL , $strDBHost = NULL , $strDBName = NULL , $strDBUser = NULL , $strDBPass = NULL , $intDBPort = NULL )
	{
		$_APPF = $this->_APPF;

		if( is_null( $strDBType ) )
		{
			$strDBType = $_APPF["DB_TYPE"];
		}
		$this->DB = NewADOConnection( $strType );
		switch( true )
		{
			case	is_null( $strDBHost ):
				$strDBHost = $_APPF["DB_HOST"];
				break;
			case	is_null( $strDBName ):
				$strDBName = $_APP["DB_NAME"];
				break;
			case	is_null( $strDBUser ):
				$strDBUser = $_APPF["DB_USER"];
				break;
			case	is_null( $strDBPass ):
				$strDBPass = $_APPF["DB_PASS"];
				break;
			case	is_null( $intDBPort ):
				$intDBPort = $_APPF["DB_PORT"];
				break;
		}
		try
		{
			$this->DB->Connect( $strHost . ( $intDBPort ? ":{$intDBPort}" : "" ) , $strDBUser , $strDBPass , $strDBName );
		}catch( ADODB_Exception $objE )
		{
			$this->SetMessage( __FILE__ , ( __LINE__ - 3 ) , $strMessage );
			return	$this;
		}
		return	$this;
	}

	protected	function	&InitLang( $strLang = NULL )
	{
		parent::InitLang( $strLang );
		if( is_null( $strLang ) )
		{
			$strLang = $this->_APPF["LANG"];
		}
		$strInnerFileName = $this->CamelCase2UnderLine( get_class( $this ) );
		$strLangFilePath = "{$this->_APPF["DIR_LANG"]}/{$strLang}/lang.{$strInnerFileName}.php";
		if( file_exsits( $strLangFilePath ) )
		{
			include( $strLangFilePath );
		}else
		{
			$strLang = "en_US";
			$strLangFilePath = "{$this->_APPF["DIR_LANG"]}/{$strLang}/lang.{$strInnerFileName}.php";
			include( $strLangFilePath );
		}
		foreach( $_LANG AS $strK => $strV )
		{
			$this->_LANG[$strK] = $strV;
		}
		return	$this;
	}

	protected	function	GetLang( $strCode , $strLang = NULL )
	{
		if( is_null( $strLang ) )
		{
			$strLang = $this->_APPF["LANG"];
		}
		//如果引入的語言碼與
		if( ! isset( $_LANG[$strCode] ) || $strLang != $this->_APPF["LANG"] )
		{
			$this->InitLang( $strLang );
		}
	}

	static	public	function	CamelCase2UnderLine( $strInput )
	{
		$aryExplode = explode( "_" , $strInput );
		$aryPreimport = array();
		foreach( $aryExplode AS $strTemp )
		{
			$aryPreimport[] = ucfirst( strtolower( $strTemp ) );
		}
		return	implode(  "" , $aryPreimport );
	}
	static	public	function	UnderLine2CamelCase( $strInput )
	{
		if( preg_match_all( '/^([A-Z0-9][a-z0-9]*)+$/' , $strInput , $aryReg ) )
		{
			$aryReturn = array();
			foreach( $aryReg AS $strPreimplode )
			{
				$aryReturn[] = strtolower( $strPreimplode );
			}
			return	implode( "_" , $aryReturn );
		}else
		{
			return	$strInput;
		}
	}
}
?>