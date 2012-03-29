<?php if (!defined('APPLICATION')) exit();

// 0.2 - 2011-09-07 - mosullivan - Added InjectCssClass, Optimized querying.
// 0.3 - 2011-12-13 - linc - Add class to title span, make injected CSS class Vanilla-like (capitalized, no dashes).
// 0.4 - 2011-09-07 - mosullivan - Consolidated role injection to a single event. 

$PluginInfo['RoleTitle'] = array(
   'Name' => 'RoleTitle',
   'Description' => "Adds user's roles under their name in comments and adds related css definitions to the comment containers.",
   'Version' => '0.4',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class RoleTitlePlugin extends Gdn_Plugin {
   /**
    * Inject the roles under the username on comments.
    */
   public function Base_AuthorInfo_Handler($Sender) {
      $Object = GetValue('Object', $Sender->EventArguments);
      $Roles = $Object ? GetValue('Roles', $Object, array()) : FALSE;
      if (!$Roles)
         return;

      echo '<span class="RoleTitle">'.implode(', ', $Roles).'</span> ';
   }

   /**
    * Inject css classes into the comment containers.
    */
   public function Base_BeforeCommentDisplay_Handler($Sender) {
      $Comment = GetValue('Comment', $Sender->EventArguments);
      if (!$Comment)
         return;

      $CssRoles = $Comment ? GetValue('Roles', $Comment, array()) : FALSE;
      if (!$CssRoles)
         return;
      
      $Sender->EventArguments['CssClass'] .= $this->_GetCssClass($CssRoles);
   }
   
   /** 
    * Define the class names based on role names.
    */
   private function _GetCssClass($Roles) {
      if (!$Roles)
         return;
      
      foreach ($Roles as &$RawRole)
         $RawRole = 'Role_'.str_replace(' ','_', Gdn_Format::AlphaNumeric($RawRole));
   
      return count($Roles) ? ' '.implode(' ', $Roles) : '';
   }

   
   /**
    * Add the insert user's roles to the comment data so we can visually
    * identify different roles in the view.
    */ 
	public function DiscussionController_Render_Before($Sender) {
		$Session = Gdn::Session();
		if ($Session->IsValid()) {
			$JoinUser = array($Session->User);
			RoleModel::SetUserRoles($JoinUser, 'UserID');
		}
		if (property_exists($Sender, 'Discussion')) {
			$JoinDiscussion = array($Sender->Discussion);
			RoleModel::SetUserRoles($JoinDiscussion, 'InsertUserID');
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
         
         // Assign the css class for the discussion here 
         $Sender->Discussion->_CssClass .= $this->_GetCssClass($Sender->Discussion->Roles);
		}
   }
   public function PostController_Render_Before($Sender) {
		if (property_exists($Sender, 'CommentData') && is_object($Sender->CommentData))
			RoleModel::SetUserRoles($Sender->CommentData->Result(), 'InsertUserID');
	}
   
}