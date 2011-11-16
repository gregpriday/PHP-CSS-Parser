<?php

/**
 * Handles 
 */
class Origin_Cache {
	private $_tmp = array();
	
	private $_last_url;
	
	/**
	 * 
	 */
	public function __destruct(){
		foreach($this->_tmp as $file)
			@unlink($file);
	}
	
	/**
	 * Gets a cache file, defined by its signature.
	 *
	 * @param string $container The container name.
	 * @param mixed $tosign A serializable object that defines the cache file.
	 * @param string $extension The file extension.
	 * @param bool $touch Should we touch the file to update it's modification time?
	 * @param bool $empty Should we empty the file?
	 */
	function get_file($container, $tosign, $extension){
		if(empty($extension)) return false;
		$sig = md5(serialize($tosign));
		
		$folder = Origin::single()->get_storage_folder($container, true);
		$filename = $folder.'/'.$sig.'.'.$extension;
		
		$this->_last_url = Origin::single()->get_storage_url($container).'/'.$sig.'.'.$extension;
		
		return $filename;
	}
	
	/**
	 * @return The full URL of the last file returned by get_file.
	 */
	function get_last_url() {
		return $this->_last_url;
	}
	
	/**
	 * Returns the filename of a temporary cache file
	 *
	 * @param string $extension The file extension.
	 */
	function get_temp_file($extension){
		$folder = Origin::single()->get_storage_folder('tmp', true);
		do{
			$tmp = path_join($folder, base_convert(rand(), 10,36).'.'.$extension);
		} while(file_exists($tmp));
		
		$this->_tmp[] = $tmp;
		return $tmp;
	}
	
	/**
	 * Clears files in the storage folder every 24 hours
	 */
	function clear_cache(){
		// Dont clear the cache if we've done so recently
		if(get_transient('origin_clear_cache') !== false) return;
		
		// Remove old cache files in the container
		$dir = Origin::single()->get_storage_folder();
		foreach(glob($dir.'/*/*.*') as $file)
			if(time() - fileatime($file) > 7*86400) unlink($file);
		
		// Record this cache clearing, and schedule another for 24 hours
		set_transient('origin_clear_cache', time(), 86400);
	}
	
	/**
	 * Gets the URL of a cache container
	 */
	public function get_container_url($container){
		return content_url('origin/cache/'.$container);
	}
}