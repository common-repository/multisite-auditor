<?php


$MSA_runFunctions = new MSA_functions(); 


class MSA_functions
{
	
	public function __construct()
	{
		
		add_action( 'after_switch_theme', array($this, 'MSA_updateTheme' ) );
		add_action( 'wpmu_new_blog', array($this, 'MSA_updateThemeNewBlog' ) );	
		add_action( 'delete_blog', array($this, 'MSA_deleteBlogActions' ) );	
		
		// When any plugin is activated
		add_action( 'activated_plugin', array($this, 'MSA_detectPluginActivation' ), 10, 2 );	
		add_action( 'deactivated_plugin', array($this, 'MSA_detectPluginDeactivation' ), 10, 2 );	
		

				
				
    }	
	
	public static function MSA_detectPluginActivation(  $pluginName, $network_activation  )
	{
		// Only add to DB if its NOT network activated
		if($network_activation<>1)
		{		
			// Get the actual plugin folder name
			$tokens = explode('/', $pluginName);
			$pluginName = $tokens[0];			
			$blogID = get_current_blog_id();		
			$blogInfo = MSA_functions::getBlogInfo($blogID);
			MSA_functions::MSA_addPluginToDB($blogInfo, $pluginName);
		}
	}
	
	public static function MSA_detectPluginDeactivation(  $pluginName, $network_activation )
	{
		global $wpdb;		
		
		if($network_activation<>1)
		{		
			// Get the actual plugin folder name
			$tokens = explode('/', $pluginName);
			$pluginName = $tokens[0];
	
			$blogID = get_current_blog_id();		
			
			$table_name = $wpdb->base_prefix . "MSA_plugins";
			// Delete the old data on the first page run
			$deleteSQL = 'DELETE FROM '.$table_name.' WHERE blogID=%d AND pluginName = %s';
			$RunQry = $wpdb->query( $wpdb->prepare(	$deleteSQL, $blogID, $pluginName ));
		}
		
	}		
	
	public static function MSA_updateTheme($newTheme)
	{
		$blogID = get_current_blog_id();
		$blogInfo = MSA_functions::getBlogInfo($blogID);
		MSA_functions::MSA_addThemeToDB($blogInfo);	
	}	
	
	public static function MSA_deleteBlogActions($blogID)
	{
		global $wpdb;		
		$table_name = $wpdb->base_prefix . "MSA_themes";					
		$deleteSQL = 'DELETE FROM '.$table_name.' WHERE blogID = %d';
		$RunQry = $wpdb->query( $wpdb->prepare(	$deleteSQL  , $blogID));
		
		
		$table_name = $wpdb->base_prefix . "MSA_plugins";					
		$deleteSQL = 'DELETE FROM '.$table_name.' WHERE blogID = %d';
		$RunQry = $wpdb->query( $wpdb->prepare(	$deleteSQL, $blogID  ));
		
	}
	
	public static function MSA_updateThemeNewBlog($blogID)
	{
		$blogInfo = MSA_functions::getBlogInfo($blogID);
		$this->MSA_addThemeToDB($blogInfo);
	}
	
	public static function getBlogTheme($blogID)
	{
		switch_to_blog($blogID);	
		$theme = get_stylesheet_directory_uri();
		$tokens = explode('/', $theme);
		$themeName = trim(end($tokens));
		return $themeName;
	}
	
	public static function getBlogInfo($blogID)
	{
		switch_to_blog($blogID);
		
		$themeName = MSA_functions::getBlogTheme($blogID);
		
		// Get the rest of the general info
		$blogInfoArray = get_blog_details($blogID);
		$blogName = $blogInfoArray->blogname;
		$blogURL = $blogInfoArray->path;
		$dateCreated = $blogInfoArray->registered;	
		
		$blogInfo = array(
			'blogID'=>$blogID,
			'themeName'=>$themeName,
			'blogURL'=>$blogURL,
			'blogName'=>$blogName,
			'dateCreated'=>$dateCreated
		);		
		
		return $blogInfo;
		
	}
	
	public static function MSA_addThemeToDB($blogInfo)
	{
		global $wpdb;
		
		$blogID = $blogInfo['blogID'];	
		$themeName = $blogInfo['themeName'];
		$blogName = $blogInfo['blogName'];
		$blogURL = $blogInfo['blogURL'];
		$dateCreated = $blogInfo['dateCreated'];
		$myDate= date('Y-m-d h:i:s');		
		
		// Delete from theme database any old data about this blog
		$table_name = $wpdb->base_prefix . "MSA_themes";					
		$deleteSQL = 'DELETE FROM '.$table_name.' WHERE blogID = %d';
		
		$RunQry = $wpdb->query( $wpdb->prepare(	$deleteSQL, $blogID  ));
		
		$myFields="INSERT into ".$table_name." (blogID, themeName, blogName, blogURL, dateCreated, activateDate) ";
		$myFields.="VALUES (%d, '%s', '%s', '%s', '%s', '%s')";
		
		$RunQry = $wpdb->query( $wpdb->prepare($myFields,
			$blogID,
			$themeName,
			$blogName,
			$blogURL,
			$dateCreated,
			$myDate
		));			
	}
	
	public static function MSA_addPluginToDB($blogInfo, $pluginName)
	{
		global $wpdb;
		
		$blogID = $blogInfo['blogID'];	
		$blogName = $blogInfo['blogName'];
		$blogURL = $blogInfo['blogURL'];
		$dateCreated = $blogInfo['dateCreated'];
		
		
		// Delete from theme database any old data about this blog
		$table_name = $wpdb->base_prefix . "MSA_plugins";						
		$myFields="INSERT into ".$table_name." (blogID, pluginName, blogName, blogURL, dateCreated) ";
		$myFields.="VALUES (%d, '%s', '%s', '%s', '%s')";
		
		$RunQry = $wpdb->query( $wpdb->prepare($myFields,
			$blogID,
			$pluginName,
			$blogName,
			$blogURL,
			$dateCreated
		));			
	}	
	
	
	public static function MSA_updateThemeFromSwap($blogID, $newTheme)
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "MSA_themes";
		$myFields="UPDATE ".$table_name." SET themeName = '%s' WHERE blogID = %d";
		$RunQry = $wpdb->query( $wpdb->prepare($myFields,
			$newTheme,
			$blogID
		));			
	}		
	
	
	
	
	public static function getNetworkBlogList()
	{
		
		global $wpdb; 

		$mySitesArray=array();
		$table_name = $wpdb->base_prefix . "blogs";
		
		$sql = "SELECT blog_id FROM ".$table_name;
		$site_blog_ids = $wpdb->get_results($sql); // get all blog ids	
		
		foreach($site_blog_ids as $thisSite)
		{
			$blogID = $thisSite->blog_id;
			$mySitesArray[]=  $blogID;
		}
		
		return $mySitesArray;
	}
	
	public static function getBlogsOnTheme($themeName, $limitValue="")
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "MSA_themes";					
		$SQL='Select * FROM '.$table_name.' WHERE themeName="'.$themeName.'"';
		if($limitValue){$SQL.=' LIMIT '.$limitValue;}
		$blogArray = $wpdb->get_results( $SQL, ARRAY_A );
		return $blogArray;
	}	
	
	public static function getBlogsUsingPlugin($pluginName)
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "MSA_plugins";					
		$SQL='Select * FROM '.$table_name.' WHERE pluginName="'.$pluginName.'"';
		$blogArray = $wpdb->get_results( $SQL, ARRAY_A );
		return $blogArray;
	}	
	
	public static function getAllPluginsUsed()
	{
		global $wpdb;
		
		$table_name = $wpdb->base_prefix . "MSA_plugins";					
		$SQL='Select * FROM '.$table_name;
		$blogArray = $wpdb->get_results( $SQL, ARRAY_A );
		return $blogArray;
		
	}
	
	
	public static function runFirstTimeAudit()
	{
		
		global $wpdb;
		
		$minArrayVal = $_GET['blogPage'];
		$blogIncrement = 10;
		$maxArrayVal = $minArrayVal + $blogIncrement;

		
		// If its the first page then firstly clear the DB
		if($minArrayVal==0)
		{
			$table_name = $wpdb->base_prefix . "MSA_themes";
			// Delete the old data on the first page run
			$sql = 'DELETE FROM '.$table_name;
			$wpdb->query( $sql );
			
			$table_name = $wpdb->base_prefix . "MSA_plugins";
			$sql = 'DELETE FROM '.$table_name;			
			// Delete the old data on the first page run
			$wpdb->query( $sql );			
			
		}
		
		// Remove the option of last run date
		delete_option( 'MSA_run_date');
		
		
		$allSites = MSA_functions::getNetworkBlogList();
		
		$blogCount = count($allSites);
		
		if($blogCount<$minArrayVal)
		{
			?>
			<script>
			window.location.replace("admin.php?page=multisite-auditor-overview&action=auditComplete");
			</script>
			
			<?php

		}
		else
		{				
		
		
			echo '<h3>Auditing <b>'.$blogCount.'</b> blogs.</h3>';
			
			$percentComplete = round(($minArrayVal/$blogCount)*100, 2);
			
			echo '<div id="progress">';
			echo '<span id="percent">'.$percentComplete.'%</span>';
			echo '<div id="bar"></div>';
			echo '</div>';
			
			echo '<style>';
			echo '
			#progress {
				width: 500px;   
				border: 1px solid #aaa;
				position: relative;
				padding: 2px;
			}
			
			#percent {
				text-shadow: 1px 1px white;
				font-size:18px;
				position: absolute;   
				left: 50%;
				top: 25%;
			}
			
			#bar {
				height: 30px;
				background: -webkit-linear-gradient(#2ac151, #073200); /* For Safari 5.1 to 6.0 */
				background: -o-linear-gradient(#2ac151, #073200); /* For Opera 11.1 to 12.0 */
				background: -moz-linear-gradient(#2ac151, #073200); /* For Firefox 3.6 to 15 */
				background: linear-gradient(#2ac151, #073200); /* Standard syntax (must be last) */
			
			
			
			width: '.$percentComplete.'%;
			}			
			';
			
			
			echo '</style>';
			
			
			while ($minArrayVal<$maxArrayVal)
			{
				
				$blogID="";
				if (array_key_exists($minArrayVal, $allSites))
				{
					$blogID = $allSites[$minArrayVal];
				}

				
				if($blogID)
				{
					
					// Audit the Theme information					
					$themeName = MSA_functions::getBlogTheme($blogID);					
					// Get other blog details and store for quick lookup
					$blogDetails = get_blog_details($blogID);
					$blogName = $blogDetails->blogname;
					$blogURL = $blogDetails->path;	
					$dateCreated = $blogDetails->registered;	
					
					$table_name = $wpdb->base_prefix . "MSA_themes";					
					$myFields="INSERT into ".$table_name." (blogID, themeName, blogName, blogURL, dateCreated) ";
					$myFields.="VALUES (%d, '%s', '%s', '%s', '%s')";	
					

					$RunQry = $wpdb->query( $wpdb->prepare($myFields,
						$blogID,
						$themeName,
						$blogName,
						$blogURL,
						$dateCreated
					));	
					
					
					// Audit the plugin information
					$pluginArray = get_option('active_plugins'); 
					
					foreach($pluginArray as $pluginRef)
					{
						$pluginName = MSA_functions::getPluginNameFromRef($pluginRef);
						
						$table_name = $wpdb->base_prefix . "MSA_plugins";
						
						$myFields="INSERT into ".$table_name." (blogID, pluginName, blogName, blogURL, dateCreated) ";
						$myFields.="VALUES (%d, '%s', '%s', '%s', '%s')";	
						
						$RunQry = $wpdb->query( $wpdb->prepare($myFields,
							$blogID,
							$pluginName,
							$blogName,
							$blogURL,
							$dateCreated
						));							
					}
					
					echo 'Auditing blog ID :'.$blogID.' ('.$blogName.')<hr/>';
					
				}
				$minArrayVal++;			
				
			}				
			
			?>
			<script>
			window.location.replace("admin.php?page=multisite-auditor-overview&action=MSA_audit&blogPage=<?php echo $minArrayVal ?> ");
			</script>
			<?php
		}
	}	
	
	
	
	
	
	public static function getAllThemesArray()
	{
		
		global $wpdb;
		// Get list of all the actual listed themes in case some have been deleted
		$table_name = $wpdb->base_prefix . "MSA_themes";	
		$SQL="SELECT themeName FROM ".$table_name." Group By themeName";
		$allThemes = $wpdb->get_results( $SQL, ARRAY_A );
		
		$allThemeArray = array();
		foreach($allThemes as $themeName)
		{
			$allThemeArray[] = $themeName['themeName'];
		}
		
		return $allThemeArray;
	}
	
	public static function getAllSitesFromThemesTable()
	{
		
		global $wpdb;
		// Get list of all the actual listed themes in case some have been deleted
		$table_name = $wpdb->base_prefix . "MSA_themes";	
		$SQL="SELECT * FROM ".$table_name;
		$allSites = $wpdb->get_results( $SQL, ARRAY_A );
		
		return $allSites;
	}	
	
	
	
	public static function getPluginNameFromRef($pluginRef)
	{
		$tempPluginNameArray = explode("/", $pluginRef, 2);
		$pluginName = $tempPluginNameArray[0];
		return $pluginName;
	
	}

}






  
?>