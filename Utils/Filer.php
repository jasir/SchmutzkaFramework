<?php

/**
 * This file is part of Schmutzka Framework
 *
 * Copyright (c) 2012 Tomas Votruba (http://tomasvotruba.cz)
 *
 * For the full copyright and license information, please view
 * the file license.md that was distributed with this source code.
 */

namespace Schmutzka\Utils;

use Schmutzka\Utils\Name;
use Nette;
use Nette\Mail\MimePart;
use Nette\Utils\MimeTypeDetector;
use Nette\Utils\Strings;
use Nette\Utils\Finder;
use Nette\Image;


class Filer extends Nette\Object
{

	/**
	 * @param  string
	 * @return  string
	 */
	public static function extension($name)
	{
		$convert = [
			'jpeg' => 'jpg'
		];

		$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
		if (isset($convert[$extension])) {
			$extension = $convert[$extension];
		}

		return $extension;
	}


	/**
	 * @param string
	 * @return  string
	 */
	public static function filename($name)
	{
		return pathinfo($name, PATHINFO_FILENAME);
	}


	/**
	 * Empty all folders and all files from particular folder
	 * @param string
	 */
	public static function emptyFolder($folder)
	{
		if (is_dir($folder)) {
			chmod($folder, 0777);

			foreach (Finder::findFiles('*')->from($folder) as $item => $info) {
				if (file_exists($item)) {
					unlink($item);
				}
			}

			foreach (Finder::findDirectories('*')->in($folder) as $item => $info) {
				rmdir($item);
			}
		}
	}


	/**
	 * Let download file under different name
	 * @param string
	 * @param string
	 * @param string
	 */
	public static function downloadAs($file, $name, $type = NULL)
	{
		if (is_file($file)) {
			$content = file_get_contents($file);

		} else {
			$content = $file;
		}

		header('Content-type: ' . $type ?: MimeTypeDetector::fromString($content));
		header('Content-Disposition: attachment; filename="' . $name . '"');
		readfile($file);
		die;
	}


	/**
	 * Resize to subfolder
	 * @param Nette\Http\FileUpload|Nette\Image
	 * @param string
	 * @param int|NULL
	 * @param int|NULL
	 * @param string
	 */
	public static function resizeToSubfolder($file, $dir, $width = NULL, $height = NULL, $filename)
	{
		if ($file instanceof Nette\Http\FileUpload) {
			$image = $file->toImage();

		} else {
			$image = $file;
		}

		if ($width && $height) {
			$image->resize($width, $height, Image::SHRINK_ONLY | Image::EXACT);

		} else {
			$image->resize($width, $height, Image::SHRINK_ONLY); // ignore Image::EXACT on one param NULL
		}

		$dir .= self::createFolderName($width, $height) . '/';
		if (! file_exists($dir)) {
			mkdir($dir);
		}

		$image->save($dir . $filename);
	}


	/**
	 * @param int
	 * @param int
	 * @return string
	 */
	private static function createFolderName($width = NULL, $height = NULL)
	{
		if ($width == NULL && $height == NULL) {
			throw new Exception('At least one size has to be specified.');
		}

		$name = '';
		if ($width) {
			$name .= 'w' . $width;
		}

		if ($height) {
			if ($name) {
				$name .= '_';
			}
			$name .= 'h' . $height;
		}

		return $name;
	}

}
