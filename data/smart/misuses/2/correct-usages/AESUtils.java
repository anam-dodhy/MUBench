package com.smart.sso.server.util;

import javax.crypto.Cipher;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.SecretKeySpec;

import org.springframework.util.Base64Utils;

import java.security.SecureRandom;

/**
 * 对称加密(AES)
 * 
 * @author Joe
 */
public class AESUtils {
	
	public static final String INIT_VECTOR = "RandomInitVector";

	/**
	 * 加密
	 * @param key 密钥
	 * @param value 加密数据
	 * @return
	 */
	public static String encrypt(String key, String value) {
		try {

			//randomization of the key
			byte[] keyMaterial = key.getBytes("UTF-8");
			SecureRandom secRandom = new SecureRandom();
			secRandom.nextBytes(keyMaterial);

			//randomization of the init_vector
			byte[] init_vector = INIT_VECTOR.getBytes("UTF-8");
			SecureRandom ivSecRandom = new SecureRandom();
			ivSecRandom.nextBytes(init_vector);

			IvParameterSpec iv = new IvParameterSpec(init_vector);
			SecretKeySpec skeySpec = new SecretKeySpec(keyMaterial, "AES");

			Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5PADDING");
			cipher.init(Cipher.ENCRYPT_MODE, skeySpec, iv);

			byte[] encrypted = cipher.doFinal(value.getBytes());
			return Base64Utils.encodeToString(encrypted);
		}
		catch (Exception ex) {
			ex.printStackTrace();
		}
		return null;
	}

	/**
	 * 解密
	 * @param key 密钥
	 * @param value 解密数据
	 * @return
	 */
	public static String decrypt(String key, String encrypted) {
		try {
			//randomization of the key
			byte[] keyMaterial = key.getBytes("UTF-8");
			SecureRandom secRandom = new SecureRandom();
			secRandom.nextBytes(keyMaterial);

			//randomization of the init_vector
			byte[] init_vector = INIT_VECTOR.getBytes("UTF-8");
			SecureRandom ivSecRandom = new SecureRandom();
			ivSecRandom.nextBytes(init_vector);

			IvParameterSpec iv = new IvParameterSpec(init_vector);
			SecretKeySpec skeySpec = new SecretKeySpec(keyMaterial, "AES");

			Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5PADDING");
			cipher.init(Cipher.DECRYPT_MODE, skeySpec, iv);
			byte[] original = cipher.doFinal(Base64Utils.decodeFromString(encrypted));

			return new String(original);
		}
		catch (Exception ex) {
			ex.printStackTrace();
		}
		return null;
	}

	public static void main(String[] args) {
		String key = "``11qqaazzxxccvv"; // 128 bit key
		System.out.println(encrypt(key, "123"));
		System.out.println(decrypt(key, encrypt(key, "123")));
	}
}
