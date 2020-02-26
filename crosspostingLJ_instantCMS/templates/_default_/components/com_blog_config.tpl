{* ================================================================================ *}
{* ============================= ��������� ����� ================================== *}
{* ================================================================================ *}

<form action="" method="post" name="cfgform" id="cfgform" style="margin-top:5px">
  <table width="600" border="0" cellpadding="6" cellspacing="0" style="background-color:#EBEBEB">
	<tr>
	  <td width="150"><strong>{$LANG.BLOG_TITLE}: </strong></td>
	  <td><input name="title" type="text" id="title" value="{$blog.title|escape:'html'}" style="width:420px"/></td>
	</tr>
	<tr>
	  <td><strong>{$LANG.SHOW_BLOG}:</strong></td>
	  	<td>
			<select name="allow_who" id="allow_who" style="width:425px">
				<option value="all" selected="selected" {if ($blog.allow_who == 'all')} selected {/if}>{$LANG.TO_ALL}</option>
				<option value="friends" {if ($blog.allow_who == 'friends')} selected {/if}>{$LANG.TO_MY_FRIENDS}</option>
				<option value="nobody" {if ($blog.allow_who == 'nobody')} selected {/if}>{$LANG.TO_ONLY_ME}</option>
			</select>
		</td>
	</tr>
	<tr>
	  <td><strong>{$LANG.SHOW_CAT}</strong>: </td>
	  <td>
		  <select name="showcats" id="showcats">
			<option value="1" selected="selected" {if ($blog.showcats == 1)} selected {/if}>{$LANG.YES}</option>
			<option value="0" {if ($blog.showcats == 0)} selected {/if}>{$LANG.NO}</option>
		  </select>
	  </td>
	</tr>
  </table>
{if $crosspost==1}
{if $yescrosspost}
  <table width="600" border="0" cellpadding="6" cellspacing="0" style="background-color:#EBEBEB;margin-top:6px">
	<tr>
	  <td width="150"><strong>�����������: </strong></td>
	  <td>
		  <select name="crosspost" id="crosspost" onchange="selectCrossPost()">
			<option value="0" selected="selected" {if ($blog.crosspost == 0)} selected {/if}>{$LANG.NO}</option>
			<option value="1" {if ($blog.crosspost == 1)} selected {/if}>{$LANG.YES}</option>
		  </select>
	  </td>
	</tr>
  </table>
	<table width="600" border="0" cellpadding="6" cellspacing="0" id="crosspostcfg" style="background-color:#EBEBEB;display:{if $blog.crosspost==0}none;{else}block;{/if}">
		<tr><td width=150><strong>����� LiveJournal:</strong></td><td colspan=3><input name="journal" id="journal" type="text" value="{if (!$blog.journal)}www.livejournal.com{else}{$blog.journal}{/if}" style="width:420px"/></td></tr>
		<tr><td><td colspan=3><small>���� �� ����������� LiveJournal-����������� ����, �� �� LiveJournal (��������, DeadJournal), ������� ��� ����� ���. ������������ �� ����� ������������ ������� �������� - www.livejournal.com</small></td></tr>
		<tr><td><strong>��� � ��:</strong><td><input name="loginlj" id="loginlj" type="text" value="{$blog.loginlj}" style="width:142px;"/><td><strong>������ � ��:</strong><td><input name="passlj" id="passlj" type="password" value="" style="width:142px;"/>
		<tr><td><td colspan=3><small>������� ������ ������ ���� ������ �������� ����������� ������! ���� �� �������� ���� ������ - ��� �� ������ ����������� ������.</small>
		{* <tr><td><strong>����������:</strong></td><td colspan=3><input name="�ommunity" id="�ommunity" type="text" value="{$blog.�ommunity}" style="width:420px"/></td></tr>
		<tr><td><td colspan=3><small>���� �� ������, ����� ���� ������ ���� ����������� � ����������, ������� ��� ���������� �����. �������� ���� ������ � ������ ����� ����������� ������ � �� ��������� ������������.</small> *}
		<tr><td><strong>��������� �����/������� ��� ���������� � ��:</strong></td><td colspan=3><label><input type="radio" {if ($blog.header_loc == 0)}checked="checked"{/if} value="0" name="header_loc"> ���</label><br><label><input type="radio" value="1" {if ($blog.header_loc == 1)}checked="checked"{/if} name="header_loc"> ������ ������</label><br><label><input type="radio" value="2" {if ($blog.header_loc == 2)}checked="checked"{/if} name="header_loc"> ����� ������</label></td></tr>
		<tr><td><strong>�������� ��������� ����� ��� ������������� � �����/�������:</strong></td><td colspan=3><label><input id=r1 type="radio" {if ($blog.custom_name_on == 0)}checked="checked"{/if} value="0" name="custom_name_on">������������ ��������� ����� ({$blog.title})</label><br><label><input id=r2 type="radio" value="1" {if ($blog.custom_name_on == 1)}checked="checked"{/if} name="custom_name_on">������</label><br><input onfocus="document.getElementById('r2').checked = true" name="custom_name" type="text" value="{$blog.custom_name}" style="width:420px"/></td></tr>
		<tr><td><strong>������� ������� � ������� � ��:</strong></td><td colspan=3><label><input type="radio" {if ($blog.privacy == 'public'  || !$blog.privacy)}checked="checked"{/if} value="public" name="privacy">���������</label><br><label><input type="radio" {if ($blog.privacy == 'private')}checked="checked"{/if} value="private" name="privacy">���������</label><br><label><input type="radio" {if ($blog.privacy == 'friends')}checked="checked"{/if} value="friends" name="privacy">��� ������</label><br></td></tr>
		<tr><td><strong>��� ������������ ��� [cut=������ �����...]:</strong></td><td colspan=3><label><input type="radio" {if ($blog.more == 'link')}checked="checked"{/if} value="link" name="more">������ �� ����</label><br><label><input type="radio" {if ($blog.more == 'lj-cut')}checked="checked"{/if} value="lj-cut" name="more">������������ lj-cut</label><br><label><input type="radio" {if ($blog.more == 'copy'  || !$blog.more)}checked="checked"{/if} value="copy" name="more">���������� ��� ������ � �� (��� �������)</label>
	</table>
{/if}
{/if}  
  <table width="600" border="0" cellpadding="6" cellspacing="0" style="background-color:#EBEBEB;margin-top:6px">
	<tr>
	  <td width="150"><strong>{$LANG.BLOG_TYPE}: </strong></td>
	  <td>
		  <select name="ownertype" id="ownertype" onchange="selectOwnerType()" style="width:425px">
			<option value="single" {if ($blog.ownertype == 'single')} selected {/if}>{$LANG.PERSONAL} {$min_karma_private}</option>
			<option value="multi" {if ($blog.ownertype == 'multi')} selected {/if}>{$LANG.COLLECTIVE} {$min_karma_public}</option>
		  </select>
	  </td>
	</tr>
  </table>
  <table width="600" border="0" cellpadding="6" cellspacing="0" id="multiblogcfg" style="background-color:#EBEBEB;display:{if $blog.ownertype=='single'}none;{else}block;{/if}">
	<tr>
	  <td width="150"><strong>{$LANG.PREMODER_POST}: </strong></td>
	  <td>
		  <select name="premod" id="premod" style="width:425px">
			  <option value="1" {if ($blog.premod == 1)} selected {/if}>{$LANG.ON}</option>
			  <option value="0" {if ($blog.premod == 0)} selected {/if}>{$LANG.OFF}</option>
		  </select>
	  </td>
	</tr>
	<tr>
	  <td><strong>{$LANG.WHO_CAN_WRITE_TO_BLOG}: </strong></td>
	  <td>
		  <select name="forall" id="forall" onchange="selectAuthorsType()" style="width:425px">
			  <option value="1" {if ($blog.forall == 1)} selected {/if}>{$LANG.ALL_USERS}</option>
			  <option value="0" {if ($blog.forall == 0)} selected {/if}>{$LANG.LIST_USERS}</option>
		  </select>
	  </td>
	</tr>
  </table>
  <input type="hidden" name="uid" id="uid" value="{$blog.user_id}"/>
  <table width="600" border="0" cellspacing="0" cellpadding="10" id="multiuserscfg" style="margin-top:5px;display: {if $blog.ownertype=='single' || $blog.forall}none;{else}table;{/if}">
	  <td align="center" valign="top"><strong>{$LANG.CAN_WRITE_TO_BLOG}: </strong><br/>
		<select name="authorslist[]" size="15" multiple id="authorslist" style="width:250px">
			{$authors_list}
		</select>          
	  </td>
	  <td align="center">
	  	  <div><input name="author_add" type="button" id="author_add" value="&lt;&lt;"></div>
		  <div><input name="author_remove" type="button" id="author_remove" value="&gt;&gt;" style="margin-top:4px"></div>
	  </td>
	  <td align="center" valign="top"><strong>{$LANG.ALL_USERS}:</strong><br/>
		<select name="userslist" size="15" multiple id="userslist" style="width:250px">
			{$users_list}
		</select>
	  </td>
	</tr>  
  </table>
  <p style="margin-top:20px">
	<input name="goadd" type="submit" id="goadd" value="{$LANG.SAVE_CONFIG}" />
	<input name="delete" type="button" onclick="window.location.href='/blogs/{$id}/delblog.html'" value="{$LANG.DEL_BLOG}" />
	<input name="cancel" type="button" onclick="window.history.go(-1)" value="{$LANG.CANCEL}" />
  </p>
</form>