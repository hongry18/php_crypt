function aes256_encrypt($plaintext, $password) {
	// 보안을 최대화하기 위해 비밀번호를 해싱한다.
	$password = hash('sha256', $password, true);

	// 용량 절감과 보안 향상을 위해 평문을 압축한다.
	$plaintext = gzcompress($plaintext);

	// 초기화 벡터를 생성한다.
	$iv_source = defined('MCRYPT_DEV_URANDOM') ? MCRYPT_DEV_URANDOM : MCRYPT_RAND;
	$iv = mcrypt_create_iv(32, $iv_source);

	// 암호화한다.
	$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $password, $plaintext, 'cbc', $iv);
	// 위변조 방지를 위한 HMAC 코드를 생성한다. (encrypt-then-MAC)
	$hmac = hash_hmac('sha256', $ciphertext, $password, true);
	
	// 암호문, 초기화 벡터, HMAC 코드를 합하여 반환한다.
	return base64_encode($ciphertext . $iv . $hmac);
}

/**
	복호화 함수
	@return 실패, 위변조시 boolean 정상적으로 복호화시 String
*/
function aes256_decrypt($ciphertext, $password) {
	// 초기화 벡터와 HMAC 코드를 암호문에서 분리하고 각각의 길이를 체크한다.

	$ciphertext = base64_decode($ciphertext);

	if ($ciphertext === false) {
		return false;
	}
	
	$len = strlen($ciphertext);
	if ($len < 64) {
		echo $ciphertext . '<br>';
		return false;
	}
	
	//문자열에서 위치만큼 잘라옴;;
	$iv = substr($ciphertext, $len - 64, 32);
	$hmac = substr($ciphertext, $len - 32);
	$ciphertext = substr($ciphertext, 0, $len - 64);
	
	// 암호화 함수와 같이 비밀번호를 해싱한다.
	$password = hash('sha256', $password, true);
	
	// HMAC 코드를 사용하여 위변조 여부를 체크한다.
	$hmac_check = hash_hmac('sha256', $ciphertext, $password, true);
	if ($hmac !== $hmac_check) {
		return false;
	}
	
	// 복호화한다.
	$plaintext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $password, $ciphertext, 'cbc', $iv);
	if ($plaintext === false) {
		return false;
	}
	
	// 압축을 해제하여 평문을 얻는다.
	$plaintext = gzuncompress($plaintext);
	if ($plaintext === false) {
		return false;
	}
	
	// 이상이 없는 경우 평문을 반환한다.
	return $plaintext;
}