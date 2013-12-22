<?php
include( '../conf/set.basic.php' );
include( 'function.php' );
include( "{$_APPF["DIR_3RD_PARTY"] }/adodb/adodb.inc.php" );
include( "{$_APPF["DIR_3RD_PARTY"] }/smarty/Smarty.class.php" );

/**
 * @var	integer(bitwise)	依「頁」取得資料(如：第3頁的資料)
 */
define( "SQL_FETCH_BY_PAGE" , pow( 2 , 0 ) );
/**
 * @var	integer(bitwise)	依指定區間來取得資料
 */
define( "SQL_FETCH_BY_ZONE" , pow( 2 , 1 ) );
/**
 * @var	integer(bitwise)	依指定起始位置來取得資料
 */
define( "SQL_FETCH_BY_ORIGINAL" , pow( 2 , 2 ) );

abstract	class	Core	extends	stdClass
{
	/**
	 * 系統暫存快取
	 * @var	array
	 */
	protected	$Cache = array();
	protected	$Debug = array(
		"sql" => array() ,
		"sql_history" => array() ,
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
		if( $this->_APPF["USE_MULTILANG"] )
		{
			$this->InitLang( $this->_APPF["LANG"] );
		}else
		{
			$this->InitLang( $this->_APPF["DEFAULT_LANG"] );
		}
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
	 * 設定時間追蹤
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

	/**
	 * 設定記憶體使用的追蹤
	 * @return Core
	 */
	protected	function	&SetMemTrace()
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
			$this->Debug["mem"][$aryTime["umtime"]] = memory_get_usage();
		}
		return	$this;
	}

	/**
	 * 設定SQL指令使用追蹤
	 * @param	string	$strFile	SQL指令所在的檔案位置
	 * @param	integer	$intLine	SQL指令在上述檔案位置的行數
	 * @param	string	$strSQL	SQL指令
	 * @return Core
	 */
	protected	function	&SetSQLTrace( $strFile , $intLine , $strSQL )
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
		}
		$this->Debug["sql"][$strFile][$intLine][$aryTime["umtime"]] = $strSQL;
		return	$this;
	}

	/**
	 * 設定信息追蹤
	 * @param	string	$strFile	信息記錄的檔案位置
	 * @param	integer	$intLine	信息記錄所在的行數
	 * @param	string	$strMessage	信息內容
	 * @return Core
	 */
	protected	function	&SetMessage( $strFile , $intLine , $strMessage )
	{
		if( $this->Debug["flag"] )
		{
			$aryTime = $this->GetTime();
			$this->Debug["msg"][$strFile][$intLine][$aryTime["umtime"]] = $strSQL;
		}
		return	$this;
	}
	/**
	 * 初始化DB物件
	 * @param string $strDBType 資料庫的類別(如:mysql/postgresql...)
	 * @param string $strDBHost 資料庫主機所在的位置
	 * @param string $strDBName 資料庫名稱
	 * @param string $strDBUser 資料庫使用者名稱
	 * @param string $strDBPass 資料庫使用者密碼
	 * @param string $intDBPort 資料庫所使用的網路埠號
	 * @return Core
	 */
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
	/**
	 * 判斷DB的物件是否被初始化過
	 * @param string $bolForceInited 若沒有被初始化，是否強迫初始化(一律return true)
	 * @return boolean	true:已初始化/false:未初始化
	 */
	protected	function	DBInited( $bolForceInited = true )
	{
		$strDBClass = get_class( $this->DB );
		if( ! preg_match( '/^adodb\_/i' , $strDBClass ) )
		{
			if( $bolForceInited )
			{
				$this->InitDB();
			}else
			{
				return	false;
			}
		}
		return	true;
	}
	/**
	 * 執行SQL
	 * @param integer $intCacheTime 快取時間(optional)
	 * @param string $strSQL 要執行的SQL指令
	 * @param integer $intFetchMode 取得資料的模式(SQL_FETCH_BY_PAGE|SQL_FETCH_BY_ZONE|SQL_FETCH_BY_ORIGINAL)
	 * @param integer $intCountStart 取得資料的數量(When $intFetchMode in array( SQL_FETCH_BY_PAGE , SQL_FETCH_BY_ORIGINAL ))<br/>/取得資料的開始位置(When $intFetchMode == SQL_FETCH_BY_ZONE)
	 * @param integer $intPageOffsetEnd (SQL_FETCH_BY_PAGE:取得資料所在頁數/SQL_FETCH_BY_ZONE:取得資料的結束位置/SQL_FETCH_BY_ORIGINAL:取得資料的起始位置)
	 * @param mixParam $mixParam SQL中的參數
	 * @return boolean|stdClass|ADODBRecordset_XXX
	 */
	protected	function	ExecuteSQL()
	{
		$aryParams = func_get_args();
		$i = 0;
		if( preg_match( '/^[0-9]+$/' , $aryParams[0]  ) )
		{
			$intCacheTime = $aryParams[$i++];
			$strSQL = $aryParams[$i++];
			$intFetchMode = $aryParams[$i++];
			$intCountStart = $aryParams[$i++];
			$intPageOffsetEnd = $aryParams[$i++];
			$mixParam = $aryParams[$i++];
		}else
		{
			$strSQL = $aryParams[$i++];
			$intFetchMode = $aryParams[$i++];
			$intCountStart = $aryParams[$i++];
			$intPageOffsetEnd = $aryParams[$i++];
			$mixParam = $aryParams[$i++];
		}
		//處理SQL加參數的問題:START
		if( preg_match( '/\{\$[_a-z0-9]+\}/i' , $strSQL ) )
		{
			if( is_array( $mixParam ) )
			{
				$aryParam = $mixParam;
			}else
			{
				if( isset( $mixParam ) )
				{
					$aryParam[] = $mixParam;
				}
			}
			$this->DBInited( true );
			krsort( $aryParam );
			if( $mixParam )
			{
				foreach( $aryParam AS $mixK => $mixV )
				{
					$strSQL = preg_replace( '/' . addRegExpSlashes( $mixK ) . '/' , $mixV , $strSQL );
				}
			}
		}
		//處理SQL加參數的問題:END
		//是否使用CACHE:START
		$strPreMethod = "";
		if( $intCacheTime > 0 )
		{
			$strPreMethod = "Cache";
		}
		//是否使用CACHE:END
		//決定使用取得資料的方法:START
		if( $intCountStart > 0 )
		{
			if( 0 < ( $intFetchMode & SQL_FETCH_BY_PAGE ) )
			{
				$strMethod = "{$strPreMethod}PageExecute";
			}elseif( 0 < ( $intFetchMode & SQL_FETCH_BY_ZONE ) )
			{
				//將ZONE改成ORIGINAL:START
				$strMethod = "{$strPreMethod}SelectLimit";
				$intTempStart = $intCountStart;
				$intCountStart = ( $intPageOffsetEnd - $intCountStart ) + 1;
				$intPageOffsetEnd = $intCountStart - 1;
				//將ZONE改成ORIGINAL:END
			}else
			{
				$strMethod = "{$strPreMethod}SelectLimit";
			}
		}else
		{
			$strMethod = "{$strPreMethod}Execute";
		}
		//決定使用取得資料的方法:END
		try
		{
			$aryTime = $this->GetTime();
			$strNow = date( "Y-m-d H:i:" , $aryTime["utime"] ) . ":" . ( date( "s" , $aryTime["utime"] ) + $aryTime["msec"] );
			$this->Debug["sql_history"][$strNow] = $strSQL;
			$objResult = new	stdClass();
			if( $intCountStart > 0 )
			{
				if( $intCacheTime > 0 )
				{
					$objResult = $this->DB->{$strMethod}( $intCacheTime , $strSQL , $intCountStart , $intPageOffsetEnd );
				}else
				{
					$objResult = $this->DB->{$strMethod}( $strSQL , $intCountStart , $intPageOffsetEnd );
				}
			}else
			{
				$objResult = $this->DB->{$strMethod}( $strSQL );
			}
		}catch( ADODB_Exception $objE )
		{
			$this->SetMessage( __FILE__ , __LINE__ , $this->GetLang( "ERROR_FOR_SQL_EXECUTE" , array( "REASON" => $objE->getMessage() , "SQL" => $strSQL ) ) );
			return	false;
		}
		return	$objResult;
	}
	/**
	 * 初始化語言物件
	 * @param string $strLang 要採用的語言別
	 * @return Core
	 */
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
	/**
	 * 取得詞系的字句
	 * @param string $strCode 取得多國語系的編碼
	 * @param array $aryParams 要置換的參數
	 * @param string $strLang 指定要取得的語系
	 * return string
	 */
	protected	function	GetLang( $strCode , $aryParams = array() , $strLang = NULL )
	{
		if( is_null( $strLang ) )
		{
			$strLang = $this->_APPF["LANG"];
		}
		if( ! $strLang )
		{
			$strLang = "en_US";
		}
		//如果引入的語言碼與
		if( ! isset( $this->_LANG[$strCode] ) || $strLang != $this->_APPF["LANG"] )
		{
			$this->InitLang( $strLang );
		}
		if( is_array( $aryParams ) && count( $aryParams ) > 0 )
		{
			$arySource = array();
			$aryTarget = array();
			krsort( $aryParams );
			foreach( $aryParams AS $strSource => $mixTarget )
			{
				$arySource[] = "/\{" . addslashes( strtoupper( $strSource ) ) . "\}/i";
				$aryTarget[] = $mixTarget;
			}
			return	preg_replace( $arySource , $aryTarget , $this->_LANG[$strLang][$strCode] );
		}else
		{
			return	$this->_LANG[$strLang][$strCode];
		}
	}
}
?>