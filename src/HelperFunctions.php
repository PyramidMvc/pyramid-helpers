<?php
/**
 * App          : Pyramid PHP Fremework
 * Author       : Nihat Doğan
 * Email        : info@pyramid.com
 * Website      : https://www.pyramid.com
 * Created Date : 01/01/2025
 * License GPL
 *
 */

use Dotenv\Dotenv;
use Pyramid\Container;
use Pyramid\Router;
use Pyramid\Crypt;
use Pyramid\SessionService;

//function xss_clean($string){
//$config = HTMLPurifier_Config::createDefault();
//$purifier = new HTMLPurifier($config);
//return $purifier->purify($string);
//};

$dotenv = Dotenv::createImmutable( root_path( "" ) );
$dotenv->load();

/**
 * @param $key
 * @param $default
 * env fonksiyonu oluşturuluyor
 *
 * @return mixed|void
 */
if ( ! function_exists( 'env' ) ) {
	function env( $key, $default = null ) {
		if ( isset( $_ENV[ $key ] ) ) {
			return $_ENV[ $key ];
		}
	}
}


/**
 * @param $key
 * @param $default
 * PHP Error Reporting Modu
 *
 * @return mixed|void
 */
if ( env( 'APP_DEBUG' ) === 'true' ) {
	error_reporting( E_ALL );
} else {
	error_reporting( 0 );
}








/**
 * @return false|string
 * Root dizini yolu fonksiyonu
 */

function root_path( $path ) {
	return getcwd() . $path;
}


/**
 * @return string
 * Database dizini yolu fonksiyonu
 */
if ( ! function_exists( 'database_path' ) ) {
	function database_path( $path ) {
		return root_path( "/database/{$path}" );
	}
}


if ( ! function_exists( 'repository_path' ) ) {
	function repository_path( $path ) {
		return root_path( "/repository/{$path}" );
	}
}


/**
 * @param $path
 * Base Path dizin yolu fonksiyonu
 * Eğer dosyanız /app/Controller/MyController.php dizininde ise /app/Controller döndürecektir.
 *
 * @return string
 */
if ( ! function_exists( 'base_path' ) ) {
	function base_path( $path ) {
		return __DIR__ . $path;
	}
}


/**
 * @param $path
 * App dizin yolu fonksiyonu
 *
 * @return string
 */
if ( ! function_exists( 'app_path' ) ) {
	function app_path( $path ) {
		return getcwd() . "/app/{$path}";
	}
}


/**
 * @param $path
 * View dizin yolu fonksiyonu
 *
 * @return string
 */
if ( ! function_exists( 'views_path' ) ) {
	function views_path( $path ) {
		return getcwd() . "/materials/views/{$path}";
	}
}

/**
 * @param $uri
 * Site Root url fonksiyonu
 * http://example.com/myproje url adresini http://example.com olarak döner
 *
 * @return string
 */
if ( ! function_exists( 'url' ) ) {
	function url( $uri ) {
		return ( ! empty( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . $uri;
	}
}


/**
 * @param $uri
 * Asset url fonksiyonu
 * http://example.com/public/  olarak döner
 *
 * @return string
 */
if ( ! function_exists( 'asset' ) ) {
	function asset( $uri ) {
		return ( ! empty( $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . "/public/{$uri}";
	}
}




/** Konfigürasyon dosyasını dahil ediyoruz */
$conf = require_once 'config/Configuration.php';



/**
 * @param $key
 * Config dosyası fonksiyonu
 * Config içindeki yapılandırma dosyalarındaki ayarları alır
 *
 * @return mixed
 */
if ( ! function_exists( 'config' ) ) {
	function config( $key = null ) {
		$config = [];

		/** config dizini ilk defa yüklendiyse, yapılandırma dosyalarını yükleyelim */
		if ( empty( $config ) ) {

			/** config dizinindeki tüm PHP dosyalarını yükle */
			$configFiles = glob( root_path( '/config/*.php' ) );


			/** Hariç tutmak istediğiniz dosyayı belirleyin Örnek: root_path( '/config/Configuration.php') */
			$excludeFile = root_path( '' );

			/** Filtreleme işlemi - hariç tutmak istediğiniz dosyayı kaldırın */
			$files = array_filter( $configFiles, function ( $file ) use ( $excludeFile ) {

				/** Hariç tutmak için eşitlik kontrolü yapıyoruz */
				return $file !== $excludeFile;
			} );


			/** Filtreden geçen dosyaları config dizimize aktarıyoruz */
			foreach ( $files as $file ) {
				$configKey            = basename( $file, '.php' );
				$config[ $configKey ] = require $file;

			}
		}

		/** Anahtar sağlanmadıysa tüm yapılandırmayı döndürelim */
		if ( $key === null ) {
			return $config;
		}

		/** Anahtar varsa, bu anahtara ait değeri döndürelim */
		$keys  = explode( '.', $key );
		$value = $config;

		foreach ( $keys as $keyPart ) {

			if ( isset( $value[ $keyPart ] ) ) {
				$value = $value[ $keyPart ];

			} else {
				/** Anahtar bulunamazsa null döndür */
				return null;
			}
		}

		/** Sonucu döndür */
		return $value;
	}
}






/** ServiceProvider sınıfını başlatıyoruz */
foreach ( $conf['providers'] as $providerClass ) {
	/** ServiceProvider sınıfını dinamik olarak oluşturuyoruz */
	$provider = new $providerClass();

	/** Servis sağlayıcısının register metodunu çağırıyoruz */
	$provider->register();

	/** Servis sağlayıcısının boot metodunu çağırıyoruz */
	$provider->boot();
}


/** Alias'ları çözümlemek için Config sınıfını kullanıyoruz */
foreach ( $conf['aliases'] as $alias => $class ) {
	Container::setAlias( $alias, $class );
}








/**
 * @param $key
 * @param array $replace
 * Global olarak lang fonksiyonunu tanımlıyoruz
 * lang fonksiyonunu dil çevirisi için kullanıyoruz
 *
 * @return mixed
 * @throws Exception
 */
if ( ! function_exists( 'lang' ) ) {
	function lang( $key, array $replace = [] ) {

		/** Artık alias ile servislere erişebiliriz */
		$localeService = Container::make( 'locale' );

		/** Çeviriyi alıyoruz */
		return $localeService->translate( $key, $replace );
	}
}




/**
 * @return null
 * Var_dump fonksiyonunu dd() fonksiyonuna atıyoruz
 */
if ( ! function_exists( 'dd' ) ) {
	function dd() {
		echo '<link href="' . asset( "assets/plugins/highlight/styles/monokai.min.css" ) . '" rel="stylesheet"/>';
		echo '<script src="' . asset( 'assets/plugins/highlight/highlight.min.js' ) . '"></script>';
		echo '<script src="' . asset( 'assets/plugins/highlight/languages/go.min.js' ) . '"></script>';
		echo '<style>code.hljs {border-radius: 10px !important;padding: 20px !important;font-size: 16px !important;}</style>';
		echo '<script>
    document.addEventListener("DOMContentLoaded", (event) => {
        document.querySelectorAll("pre code").forEach((el) => {
            hljs.highlightElement(el);
        });
    });
</script>';
		echo '<pre class="language-php"><code>';
		if ( is_string( func_get_args() ) ) {
			echo htmlspecialchars( func_get_args() );  // Veriyi düzgün bir formatta yazdır
		} else {
			print_r( func_get_args() );
		}
		echo '</code></pre>';
		die();  // Programın çalışmasını durdur
	}
}




/**
 * @param $data
 * Dizi'yi Object nesnesine dönüştürme fonksiyonu
 *
 * @return mixed
 */
if ( ! function_exists( 'object' ) ) {
	function object( $data ) {
		return json_decode( json_encode( $data ) );
	}
}

/**
 * @param $keys
 * Hata mesajı session alma fonksiyonu
 *
 * @return mixed
 */
if ( ! function_exists( 'error' ) ) {
	function error( $key ) {
		/** Veriyi al */
		return Crypt::decrypt($_SESSION[ Crypt::encrypt($key) ]);
	}
}


/**
 * @param $keys
 * Hata mesajı session silme fonksiyonu
 *
 * @return void|null
 */
if ( ! function_exists( 'error_del' ) ) {
	function error_del( $key ) {

	unset( $_SESSION[Crypt::encrypt($key)] );
	}
}


/**
 * @param $keys
 * Form verisi geri dönüş fonksiyonu
 *
 * @return mixed|void
 */
if ( ! function_exists( 'old' ) ) {
	function old( $keys ) {
		if ( isset( $_SESSION["old_$keys" ] )) {
			return object( Crypt::decrypt($_SESSION[ Crypt::encrypt("old_$keys") ]));
		}
	}
}


/**
 * @param $keys
 * Form verisi geri dönüş silme fonksiyonu
 *
 * @return void
 */
if ( ! function_exists( 'old_del' ) ) {
	function old_del( $keys ) {
		if ( isset(  $_SESSION[Crypt::encrypt("old_$keys")]) ) {
			unset(  $_SESSION[Crypt::encrypt("old_$keys")] );
		}
	}
}


/**
 * @return array|null
 * Tüm sessionları alma fonksiyonu
 */
if ( ! function_exists( 'session_all' ) ) {
	function session_all() {
		/** Tüm session verisini döndür */
		$data = [];
		foreach ( $_SESSION as $key => $value ) {
			$data[ Crypt::decrypt($key) ] = Crypt::decrypt( $value );
		}
		return $data ?? null;
	}
}


/**
 * @param $key
 * @param $value
 * Session'a veri ekleme & anahtar ile veri alma & Tüm sessionları alma fonksiyonu
 *
 * @return array|mixed|void|null
 */
if ( ! function_exists( 'session' ) ) {
	function session( $key = null, $value = null ) {
		if ( $key && $value ) {
			/** Veriyi set et */
			$_SESSION[ Crypt::encrypt($key) ] = Crypt::encrypt( $value );
		} elseif ( $key ) {
			/** Veriyi al */
			return Crypt::decrypt($_SESSION[ Crypt::encrypt($key) ]);
		} else {
			/** Tüm session verisini döndür */
			$data = [];
			foreach ( $_SESSION as $key => $value ) {
				$data[ Crypt::decrypt($key) ] = Crypt::decrypt( $value );
			}
			return $data ?? null;
		}
	}
}


/**
 * @param $key
 * @param $value
 * Session'a veri ekleme fonksiyonu
 *
 * @return void
 */
if ( ! function_exists( 'session_set' ) ) {
	function session_set( $key, $value ) {
		/** Veriyi set et */
		$_SESSION[ Crypt::encrypt($key) ] = Crypt::encrypt( $value );
	}
}


/**
 * @param $key
 * Session'dan veri alma fonksiyonu
 *
 * @return mixed
 */
if ( ! function_exists( 'session_get' ) ) {
	function session_get( $key ) {
		/** Veriyi al */
		return Crypt::decrypt($_SESSION[ Crypt::encrypt($key) ]);
	}
}


/**
 * @param $key
 * Session anahtar ile oturum kontrol fonksiyonu
 *
 * @return bool
 */
if ( ! function_exists( 'session_hash' ) ) {
	function session_hash( $key ) {
		/** Veriyi al */
		return isset($_SESSION[ Crypt::encrypt($key) ]);
	}
}


/**
 * @param $key
 * Session'dan anahtar ile veri silme fonksiyonu
 *
 * @return void
 */
if ( ! function_exists( 'session_del' ) ) {
	function session_del( $key ) {
		unset($_SESSION[ Crypt::encrypt($key) ]);
	}
}


/**
 * Tüm session verilerini temizleme fonksiyonu
 * @return void
 */
if ( ! function_exists( 'session_clear' ) ) {
	function session_clear() {
		session_destroy();
	}
}


/**
 * @param $name
 * Rota yönlendirme fonksiyonu
 *
 * @return null
 */
if ( ! function_exists( 'route' ) ) {
	function route( $name, $id = '' ) {
		/** Route url'ye eşitleme işlemi yapıyoruz */
		return Router::route( $name, $id );
	}
}

function tam_url() {
	// Tam URL'yi almak için:
	$protocol   = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http'; // Protokol (http veya https)
	$host       = $_SERVER['HTTP_HOST']; // Sunucu adı (domain)
	$requestUri = $_SERVER['REQUEST_URI']; // URL'nin path ve query kısmı
// Tam URL
	return $protocol . '://' . $host . $requestUri;
}

session( 'activeLink', tam_url() );

