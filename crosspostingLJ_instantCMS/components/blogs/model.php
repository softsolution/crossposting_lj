<?php
/******************************************************************************/
//                                                                            //
//                             InstantCMS v1.8                                //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2010                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/

if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

class cms_model_blogs{

	function __construct(){
        $this->inDB = cmsDatabase::getInstance();
    }

    public function install(){

        return true;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

   //
   // этот метод вызывается компонентом comments при создании нового комментария
   //
   // метод должен вернуть массив содержащий ссылку и заголовок поста, к которому
   // добавляется комментарий
   //
   public function getCommentTarget($target, $target_id) {

        $result = array();

        switch($target){

            case 'blog': $sql = "SELECT p.title as title,
                                        p.seolink as seolink,
                                        b.seolink as bloglink
                                 FROM cms_blog_posts p
								 LEFT JOIN cms_blogs b ON b.id = p.blog_id
                                 WHERE p.id={$target_id}
                                 LIMIT 1";
                         $res = $this->inDB->query($sql);
                         if (!$this->inDB->num_rows($res)){ return false; }
                         $post = $this->inDB->fetch_assoc($res);
                         $result['link']  = $this->getPostURL(null, $post['bloglink'], $post['seolink']);
                         $result['title'] = $post['title'];
                         break;

        }

        return ($result ? $result : false);

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    // 
    // этот метод является хуком и вызывается при изменении рейтинга объекта blogpost
    // см. таблицу cms_rating_targets
    //
    public function updateRatingHook($target, $item_id, $points) {

        if ($target != 'blogpost' || !$item_id || abs($points)!=1) { return false; }

        $sql = "UPDATE cms_blogs b, cms_blog_posts p
                SET b.rating = b.rating + ({$points})
                WHERE p.blog_id = b.id AND p.id = {$item_id}";

        $this->inDB->query($sql);

        return true;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlog($id){

        $sql = "SELECT *
				FROM cms_blogs
				WHERE id = '$id'
				LIMIT 1";
		$result = $this->inDB->query($sql);

        $blog = $this->inDB->num_rows($result) ? $this->inDB->fetch_assoc($result) : false;
        $blog = cmsCore::callEvent('GET_BLOG', $blog);
		$blog['pubdate'] = cmsCore::dateFormat($blog['pubdate']);

		return $blog;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getUserBlogId($user_id){

        $blog_id = $this->inDB->get_field('cms_blogs', "user_id={$user_id} AND owner='user'", "id");

        return $blog_id ? $blog_id : false;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogByLink($seolink){

        $sql = "SELECT *
				FROM cms_blogs
				WHERE seolink = '$seolink'
				LIMIT 1";
		$result = $this->inDB->query($sql);

		if ($this->inDB->num_rows($result)) {
			$blog = $this->inDB->fetch_assoc($result);	
			$blog = cmsCore::callEvent('GET_BLOG', $blog);
			$blog['pubdate'] = cmsCore::dateFormat($blog['pubdate']);			
		}

		return $blog;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostSeoLink($post){

        $seolink = cmsCore::strToURL($post['title']);

        if ($post['id']){
            $where = ' AND id<>'.$post['id'];
        } else {
            $where = '';
        }

        $is_exists = $this->inDB->rows_count('cms_blog_posts', "seolink='{$seolink}'".$where, 1);

        if ($is_exists) { $seolink .= '-' . $post['id']; }

        return $seolink;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogSeoLink($blog){

        if ($blog['owner'] == 'user'){
            $seolink = cmsCore::strToURL($blog['title']);
        }

        if ($blog['owner'] == 'club'){
            $club    = $this->inDB->get_field('cms_clubs', "id = {$blog['user_id']}", 'title');
            $seolink = cmsCore::strToURL($club);
        }

        if ($blog['id']){
            $where = ' AND id<>'.$blog['id'];
        } else {
            $where = '';
        }

        $is_exists = $this->inDB->rows_count('cms_blogs', "seolink='{$seolink}'".$where, 1);
        if ($is_exists) { $seolink .= '-' . $blog['id']; }

        //Обновляем пути всех постов этого блога
        $sql = "SELECT id, title FROM cms_blog_posts WHERE blog_id = {$blog['id']}";

        $result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){

            while($post = $this->inDB->fetch_assoc($result)){

                $post_seolink = $this->getPostSeoLink(array('id'=>$post['id'], 'title'=>$post['title']));

                $this->inDB->query("UPDATE cms_blog_posts SET seolink='{$post_seolink}' WHERE id={$post['id']}");

            }

        }

        return $seolink;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostShort($post_content, $post_url = false, $is_after = false){

        $regex      = '/\[(cut=)\s*(.*?)\]/i';
        $matches    = array();
        preg_match_all( $regex, $post_content, $matches, PREG_SET_ORDER );

        if (is_array($matches)){

            $elm        = $matches[0];
            $elm[0]     = str_replace('[', '', $elm[0]);
            $elm[0]     = str_replace(']', '', $elm[0]);

            parse_str( $elm[0], $args );

            $cut_title  = $args['cut'];

            $pages  = preg_split( $regex, $post_content );

            if ($pages) { $post_content = $is_after ? $pages[1] : $pages[0]; }

			if ($post_url && !$is_after) {
            $post_content .= '<div class="blog_cut_link">
                                    <a href="'.$post_url.'">'.$cut_title.'</a>
                              </div>';
			}

        }

        return $post_content;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostCut($post_content){

        $regex      = '/\[(cut=)\s*(.*?)\]/i';
        $matches    = array();
        preg_match_all( $regex, $post_content, $matches, PREG_SET_ORDER );

        if (is_array($matches)){

            $elm        = $matches[0];
            $elm[0]     = str_replace('[', '', $elm[0]);
            $elm[0]     = str_replace(']', '', $elm[0]);

            parse_str( $elm[0], $args );
			
			$cut .= '[cut='.$args['cut'].'...]';

        }

        return $cut;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostURL($menuid, $bloglink, $seolink){

        $url = '/blogs/'.$bloglink.'/'.$seolink.'.html';

        return $url;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogURL($menuid, $bloglink, $page=1, $cat_id=0){

        $cat_section  = ($cat_id >0 ? '/cat-'.$cat_id   : '');
        $page_section = ($page   >1 ? '/page-'.$page    : '');

        $url = '/blogs/'.$bloglink.$cat_section.$page_section;

        return $url;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function addBlog($item){

        if (!$item['forall']) { $item['forall'] = 0; }
        if (!$item['owner']) { $item['owner'] = 'user'; }

        $item['seolink'] = '';

        $item       = cmsCore::callEvent('ADD_BLOG', $item);
        
        $sql        = "INSERT INTO cms_blogs (user_id, title, pubdate, allow_who, ownertype, premod, forall, owner, seolink, crosspost, journal, loginlj, passlj, сommunity, header_loc, custom_name_on, custom_name, privacy, more)
                       VALUES ('{$item['user_id']}', '{$item['title']}', NOW(), '{$item['allow_who']}', '{$item['ownertype']}', 0,
                               {$item['forall']}, '{$item['owner']}', '{$item['seolink']}', '{$item['crosspost']}', '{$item['journal']}', '{$item['loginlj']}', '{$item['passlj']}', '{$item['сommunity']}', '{$item['header_loc']}', '{$item['custom_name_on']}', '{$item['custom_name']}', '{$item['privacy']}', '{$item['more']}')";
        
        $result     = $this->inDB->query($sql);
        $blog_id    = $this->inDB->get_last_id('cms_blogs');

        if ($blog_id){
            
            $item['id'] = $blog_id;
            $item['seolink'] = $this->getBlogSeoLink($item);

            $this->inDB->query("UPDATE cms_blogs SET seolink='{$item['seolink']}' WHERE id = {$blog_id}");

        }

        return $blog_id ? $blog_id : false;
        
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function updateBlogAuthors($id, $authors){

        //Удаляем прежний набор авторов
        $this->inDB->query("DELETE FROM cms_blog_authors WHERE blog_id = ".$id);

        $authors = cmsCore::callEvent('UPDATE_BLOG_AUTHORS', $authors);

        //Сохраняем всех авторов из нового списка в базу
        foreach ($authors as $key=>$author_id){
            $author_id = (int)$author_id;
            $sql = "INSERT INTO cms_blog_authors (user_id, blog_id, description, startdate)
                    VALUES ($author_id, $id, '', NOW())";
            $this->inDB->query($sql);
        }

        return true;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function updateBlog($id, $item, $update_seo_link = 0, $crosspost, $yescrosspost){

        if (!$item['forall']) { $item['forall'] = 0; }
        if (!$item['owner']) { $item['owner'] = 'user'; }
        
        $item['id']         = $id;
		
		if ($update_seo_link){
        	$item['seolink']    = $this->getBlogSeoLink($item);
			$seo_sql = ', seolink = "'.$item['seolink'].'"';
		}

        $item = cmsCore::callEvent('UPDATE_BLOG', $item);

        //Сохраняем настройки блога
        $sql = "UPDATE cms_blogs
                SET title='{$item['title']}',
                    allow_who='{$item['allow_who']}',
                    showcats={$item['showcats']},
                    ownertype='{$item['ownertype']}',
                    premod={$item['premod']},
                    forall={$item['forall']},";
//исправляем баг с сохранением настроек
if (($yescrosspost && $crosspost==1) || $item['passlj'] !="") {$sql .= "owner='{$item['owner']}'{$seo_sql},";} else {$sql .= "owner='{$item['owner']}'{$seo_sql}";}
if (($yescrosspost) && $crosspost==1)
{
			$sql .= "crosspost='{$item['crosspost']}',
					journal='{$item['journal']}',
					loginlj='{$item['loginlj']}',";
}
if ($item['passlj'] !="")
{
			$sql .= "passlj='{$item['passlj']}',";
}	
if (($yescrosspost) && $crosspost==1)
{		$sql .=	"сommunity='{$item['сommunity']}',
				header_loc='{$item['header_loc']}',
				custom_name_on='{$item['custom_name_on']}',
				custom_name='{$item['custom_name']}',
				privacy='{$item['privacy']}',
				more='{$item['more']}'";
}           
		$sql .=	"WHERE id = '$id'";

        $this->inDB->query($sql);

		if ($update_seo_link){
			//обновляем ссылки меню
			$menuid = $this->inDB->get_field('cms_menu', "linktype='blog' AND linkid={$id}", 'id');
			if ($menuid){
				$inCore     = cmsCore::getInstance();
				$menulink   = $inCore->getMenuLink('blog', $id, $menuid);
				$this->inDB->query("UPDATE cms_menu SET link='{$menulink}' WHERE id={$menuid}");
			}
	
			//обновляем ссылки на комментарии постов блога
			$comments_sql = "UPDATE cms_comments c,
									cms_blog_posts p,
									cms_blogs b
							 SET c.target_link = CONCAT('/blogs/', b.seolink, '/', p.seolink, '.html')
							 WHERE b.id = {$id} AND
								   p.blog_id = b.id AND
								   c.target = 'blog' AND c.target_id = p.id";
	
			$this->inDB->query($comments_sql);
		}

        return $item['seolink'] ? $item['seolink'] : true;
        
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getSingleBlogsCount(){
        return $this->inDB->rows_count('cms_blogs', "ownertype='single' AND owner='user'");
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getMultiBlogsCount(){
        return $this->inDB->rows_count('cms_blogs', "ownertype='multi' AND owner='user'");
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogs($ownertype, $page, $perpage){
        $list = array();

        //Формируем запрос
        $sql = "SELECT u.id, b. * , u.id AS uid, u.nickname AS author, u.login as author_login, 
                       COUNT(p.id) as records,
                       b.rating AS points
                FROM cms_blogs b
				LEFT JOIN cms_users u ON u.id = b.user_id
                LEFT JOIN cms_blog_posts p ON p.blog_id = b.id ";

        //Добавляем к запросу ограничение по типу хозяина (пользователи или клубы)
        if ($ownertype!='all') { 
            $sql .= "WHERE ownertype='$ownertype' AND owner='user'\n";
        } else {
            $sql .= "WHERE owner='user'";
        }

        $sql .= "GROUP BY b.id
                 ORDER BY rating DESC";
		// если передали страницу и кол-во страниц, то добавляем LIMIT
		if ($page && $perpage) { $sql .= " LIMIT ".(($page-1)*$perpage).", $perpage"; }

        $result = $this->inDB->query($sql);

        while($blog = $this->inDB->fetch_assoc($result)){
			$blog['pubdate'] = cmsCore::dateFormat($blog['pubdate']);
            $list[] = $blog;
        }

        return $list ? cmsCore::callEvent('GET_BLOGS', $list) : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostsCount($blog_id, $category_id, $owner){

        if ($category_id != -1){
            $cat_sql = "AND p.cat_id = {$category_id}";
        } else {
            $cat_sql = '';
        }

        $sql = "SELECT p.id
                FROM cms_blogs b
				LEFT JOIN cms_blog_posts p ON p.blog_id = b.id
                WHERE b.id = '$blog_id' AND p.published = 1 AND b.owner = '$owner' {$cat_sql}
                ";
        $result = $this->inDB->query($sql);
        return $this->inDB->num_rows($result);
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPosts($blog_id, $page=0, $perpage=0, $category_id=0, $owner='user'){
        $list = array();

        if ($category_id != -1){
            $cat_sql = "AND p.cat_id = {$category_id}";
        } else {
            $cat_sql = '';
        }

        //Получаем записи, относящиеся к нужной странице блога
        $sql = "SELECT p.*, 
                       IFNULL(r.total_rating, 0) as points, u.nickname as author, u.id as author_id
                FROM cms_blogs b
				LEFT JOIN cms_blog_posts p ON p.blog_id = b.id
                LEFT JOIN cms_ratings_total r ON r.item_id=p.id AND r.target='blogpost'
				LEFT JOIN cms_users u ON u.id = p.user_id
                WHERE b.id = $blog_id AND p.published = 1 AND b.owner = '$owner' {$cat_sql}
                ORDER BY p.pubdate DESC";

        if ($page && $perpage) { $sql .= " LIMIT ".(($page-1)*$perpage).", $perpage"; }

        $result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){
            while($post = $this->inDB->fetch_assoc($result)){
				$post['fpubdate'] = cmsCore::dateFormat($post['pubdate']);
                $list[] = $post;
            }
        }

        return $list ?  cmsCore::callEvent('GET_POSTS', $list) : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPost($post_id){

	$sql = "SELECT p.*,
                   u.nickname as author,
                   u.login as author_login, 
                   u.id as author_id,
                   b.seolink as bloglink,
				   p.seolink as postlink,
				   p.ljID as ljID
			FROM cms_blog_posts p
			LEFT JOIN cms_blogs b ON b.id = p.blog_id
			LEFT JOIN cms_users u ON u.id = p.user_id
			WHERE p.id = $post_id LIMIT 1";

		$result = $this->inDB->query($sql);
		$post   = $this->inDB->num_rows($result) ? $this->inDB->fetch_assoc($result) : false;

        if ($post){  $post = cmsCore::callEvent('GET_POST', $post); }

        return $post;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getPostByLink($bloglink, $seolink){

	$sql = "SELECT p.*,
                   u.nickname as author,
                   u.id as author_id, 
                   up.imageurl as author_image,
                   u.is_deleted as author_deleted
			FROM cms_blog_posts p
			LEFT JOIN cms_users u ON u.id = p.user_id
            LEFT JOIN cms_user_profiles up ON up.user_id = p.user_id
			LEFT JOIN cms_blogs b ON b.id = p.blog_id AND b.seolink = '$bloglink'
			WHERE p.seolink = '$seolink'
            LIMIT 1";

		$result = $this->inDB->query($sql);
		$post   = $this->inDB->num_rows($result) ? $this->inDB->fetch_assoc($result) : false;

        if ($post){ 
			$post = cmsCore::callEvent('GET_POST', $post);
			$post['fpubdate'] = cmsCore::dateFormat($post['pubdate']);
			$post['feditdate'] = cmsCore::dateFormat($post['edit_date']);
		}

        return $post;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getLatestPosts($page=1, $perpage=20){
        $list = array();

        $sql = "SELECT p.*, p.pubdate as fpubdate,
                       IFNULL(r.total_rating, 0) as points,
                       u.nickname as author, u.id as author_id, u.login,
                       b.allow_who blog_allow_who,
                       b.seolink bloglink,
                       b.title blog_title,
                       b.owner owner,
                       c.title as club_title,
                       c.clubtype as club_type
                FROM cms_blog_posts p
				LEFT JOIN cms_users u ON u.id = p.user_id				
				LEFT JOIN cms_blogs b ON b.id = p.blog_id
                LEFT JOIN cms_clubs c ON c.id = b.user_id
                LEFT JOIN cms_ratings_total r ON r.item_id=p.id AND r.target='blogpost'
                WHERE p.published = 1
                ORDER BY p.pubdate DESC
                LIMIT ".(($page-1)*$perpage).", $perpage";

        $result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){
            while($post = $this->inDB->fetch_assoc($result)){
                if ($post['owner']=='club'){
                    $post['blog_title'] = $post['club_title'];
                    if ($post['club_type']=='private') { $post['content_html'] = ''; }
                }
                $list[] = $post;
            }
        }

        return $list ?  cmsCore::callEvent('GET_LATEST_POSTS', $list) : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBestPosts($page=1, $perpage=20){
        $list = array();

        $sql = "SELECT  p.*,
                        IFNULL(r.total_rating, 0) as points,
                        u.nickname as author,
                        u.id as author_id,
                        b.allow_who blog_allow_who,
                        b.seolink bloglink,
                        b.owner
                FROM cms_blog_posts p
				LEFT JOIN cms_users u ON u.id = p.user_id				
				LEFT JOIN cms_blogs b ON b.id = p.blog_id
                LEFT JOIN cms_ratings_total r ON r.item_id=p.id AND r.target='blogpost'
                WHERE p.published = 1 AND DATEDIFF(NOW(), p.pubdate) <= 7 AND b.owner = 'user'
                ORDER BY points DESC
                LIMIT ".(($page-1)*$perpage).", $perpage";

        $result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){
            while($post = $this->inDB->fetch_assoc($result)){
                $list[] = $post;
            }
        }

        return $list ?  cmsCore::callEvent('GET_BEST_POSTS', $list) : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogCategory($cat_id){
		$sql    = "SELECT * FROM cms_blog_cats WHERE id = $cat_id";
		$result = $this->inDB->query($sql);
		$cat    = $this->inDB->num_rows($result) ? $this->inDB->fetch_assoc($result) : false;
        if ($cat) { $cat = cmsCore::callEvent('GET_BLOG_CAT', $cat); }
        return $cat;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getModerationCount($blog_id){
        if (!$blog_id) { return false; }
        return $this->inDB->rows_count('cms_blog_posts', 'blog_id='.$blog_id.' AND published = 0');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getLatestCount($user_id = 0, $is_admin = 0){

        $sql = "SELECT p.user_id, b.allow_who
				FROM cms_blog_posts p
				LEFT JOIN cms_blogs b ON b.id = p.blog_id
				WHERE p.published = 1";

		$result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){

            while($post = $this->inDB->fetch_assoc($result)){

                $can_view = ($post['allow_who'] == 'all' || ($post['allow_who'] == 'friends' && usrIsFriends($post['user_id'], $user_id)) || $post['user_id']==$user_id || $is_admin);

                if ($can_view){
                    $posts[] = $post;
                }

            }
        }

        return sizeof($posts);
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBestCount($user_id = 0, $is_admin = 0){

		$sql = "SELECT p.user_id, b.allow_who
				FROM cms_blog_posts p
				LEFT JOIN cms_blogs b ON b.id = p.blog_id
				WHERE p.published = 1 AND DATEDIFF(NOW(), p.pubdate) <= 7
				";
		$result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){

            while($post = $this->inDB->fetch_assoc($result)){

                $can_view = ($post['allow_who'] == 'all' || ($post['allow_who'] == 'friends' && usrIsFriends($post['user_id'], $user_id)) || $post['user_id']==$user_id || $is_admin);

                if ($can_view){
                    $posts[] = $post;
                }

            }
        }

        return sizeof($posts);
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getModerationPosts($blog_id){

        $list = array();

        $sql = "SELECT p.*, u.nickname as author, u.id as author_id,
                       b.seolink as bloglink,
                       u.login as author_login
                FROM cms_blog_posts p
				INNER JOIN cms_blogs b ON b.id = p.blog_id AND b.id = '$blog_id'
				LEFT JOIN cms_users u ON u.id = p.user_id
                WHERE p.published = 0
                ORDER BY p.pubdate DESC";
        $result  = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){
            while($post = $this->inDB->fetch_assoc($result)){
                $list[] = $post;
            }
        }

        return $list ?  cmsCore::callEvent('GET_MODER_POSTS', $list) : false;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBlogAuthors($blog_id){

        $list = array();

        //Получаем список авторов
        $sql = "SELECT a.*, 
                       p.imageurl as imageurl,
                       u.nickname as nickname,
                       u.id as user_id,
                       u.login as user_login
                FROM cms_blog_authors a
                LEFT JOIN cms_user_profiles p ON p.user_id=a.user_id
                LEFT JOIN cms_users u ON p.user_id=u.id
                WHERE a.blog_id = {$blog_id}
                ORDER BY u.nickname ASC";

        $result = $this->inDB->query($sql);

        if ($this->inDB->num_rows($result)){
            while($author = $this->inDB->fetch_assoc($result)){
                $list[] = $author;
            }
        }

        return $list ?  cmsCore::callEvent('GET_BLOG_AUTHORS', $item) : false;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function isUserAuthor($blog_id, $user_id){
        return $this->inDB->get_field('cms_blog_authors', 'blog_id='.$blog_id.' AND user_id='.$user_id, 'id');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function isUserBlogAuthor($blog_id, $post_id, $blog_user_id){
		
		$inUser = cmsUser::getInstance();

		$blog_id_sql = $this->inDB->get_field('cms_blog_posts', "id='$post_id'", 'blog_id');
		
		$this_blog_post = ($blog_id_sql == $blog_id) ? true : false;
		
		$is_my_blog = ($blog_user_id == $inUser->id) ? true : false;

        return ($this_blog_post && $is_my_blog) ? true : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function isUserPostAuthor($post_id, $user_id){
        return $this->inDB->get_field('cms_blog_posts', 'id='.$post_id.' AND user_id='.$user_id, 'id');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getClubBlogMinKarma($club_id){
        return $this->inDB->get_field('cms_clubs', 'id='.$club_id, 'blog_min_karma');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function addPost($item){

        $item = cmsCore::callEvent('ADD_POST', $item);

        $item['seolink'] = '';

        //парсим bb-код перед записью в базу
        $inCore                 = cmsCore::getInstance();
		// Парсим по отдельности части текста, если есть тег [cut
        if (strstr($item['content'], '[cut')){
            $msg_to 	= $this->getPostShort($item['content']);
			$msg_to 	= $inCore->parseSmiles($msg_to, true);
			$msg_after 	= $this->getPostShort($item['content'], false, true);
			$msg_after 	= $inCore->parseSmiles($msg_after, true);
			$cut        = $this->getPostCut($item['content']);
			$item['content_html'] = $msg_to . $cut . $msg_after;
        } else {
        $item['content_html']   = $inCore->parseSmiles($item['content'], true);
}
		// Экранируем специальные символы
        $item['content']        = $this->inDB->escape_string($item['content']);
        $item['content_cod'] 	= $item['content_html'];//переменная для передачи в функцию кроспостинга
        $item['content_html']   = $this->inDB->escape_string($item['content_html']);
        $item['blogid'] 	= $item['id'];//переменная для передачи в функцию кроспостинга
        $sql = "INSERT INTO cms_blog_posts (user_id, cat_id, blog_id, pubdate, title, feel, music,
                            content, content_html, allow_who, edit_times, edit_date, published, seolink, comments)
                VALUES ('{$item['user_id']}', '{$item['cat_id']}', '{$item['id']}', NOW(),
                        '{$item['title']}', '{$item['feel']}', '{$item['music']}', '{$item['content']}', '{$item['content_html']}',
                        '{$item['allow_who']}', 0, NOW(), '{$item['published']}', '{$item['seolink']}', '{$item['comments']}')";
        
        $result = $this->inDB->query($sql);

        $post_id = $this->inDB->get_last_id('cms_blog_posts');

        cmsInsertTags($item['tags'], 'blogpost', $post_id);

        if ($post_id){

            $item['id']      = $post_id;
            $item['seolink'] = $this->getPostSeoLink($item);            

            $this->inDB->query("UPDATE cms_blog_posts SET seolink='{$item['seolink']}' WHERE id = '{$post_id}'");
			
			if ($item['published'] && $item['ballow_who'] == 'all') {
            	cmsCore::callEvent('ADD_POST_DONE', $item);
			}
        }
		if ($item['crosspost']==1){
			if ($item['yescrosspost']) {
			$this->addPostLJ($item['blogid'], $item['title'], $item['content_cod'], $item['tags'], $item['id'], $item['seolink']);
			}
		}
		return $post_id ? $post_id : false;
        
    }
/* ==================================================================================================== */
/* ========================Кросспостинг в ЖЖ=========================================================== */
public function addPostLJ($item_id, $item_title, $item_content, $item_tags, $post_id, $item_seolink){
//$inConf = cmsConfig::getInstance();
// подключаем библиотеку для отправки сообщения в ЖЖ
require(PATH.'/plugins/p_ping/IXR_Library.php');

// вытаскиваем настройки блога

$sql = "SELECT *
		FROM cms_blogs
		WHERE id = '$item_id'
		LIMIT 1";
$result = $this->inDB->query($sql);

$blog = $this->inDB->fetch_assoc($result);
//используемые переменные
//$blog['journal']
//$blog['crosspost']
//$blog['loginlj']
//$blog['passlj']
//$blog['more'] - принимает значения = lj-cut, link, copy

if ($blog['crosspost']==1) {
//Разбиваем текст поста на 2 части по тегу [cut=...] и оставляем только первую из них
if (strstr($item_content, '[cut')){
//подкат в ЖЖ <lj-cut>текст скрываемой записи</lj-cut>

            $msg_to 	= $this->getPostShort($item_content);
			//$msg_to 	= $inCore->parseSmiles($msg_to, true);мы уже пропарсили код в предыдущей функции
			$msg_after 	= $this->getPostShort($item_content, false, true);
			//$msg_after 	= $inCore->parseSmiles($msg_after, true);мы уже пропарсили код в предыдущей функции
			//$cut        = $this->getPostCutLj($item_content);
			//$item_content = $msg_to . $cut . $msg_after;        
			if ($blog['more']==='link'){
			$item_content = $msg_to.'<br /><a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>Читать далее...</a>';
			} elseif ($blog['more']==='lj-cut') {
			$item_content = $msg_to.' <lj-cut>'.$msg_after.'</lj-cut>';
			} elseif ($blog['more']==='copy') {
			$item_content = $msg_to.$msg_after;
			}
}

//дата для отправки поста в журнал
$datenow = getdate();
foreach ( $datenow as $key => $val )
$nameofblog = $blog['custom_name_on'] == 1 ? $blog['custom_name'] : $blog['title'];
//if ($blog['custom_name_on'] == 1) {$nameofblog = $blog['custom_name'];} else {$nameofblog = $blog['title'];}
//запись в зависимости от настроек
if ($blog['header_loc']==1) {
$item_content = '<p>Исходная запись опубликована здесь - <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>'.$nameofblog.'</a>. Вы можете оставить свой комментарий в этом журнале или <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>на сайте '.$_SERVER['HTTP_HOST'].'</a></p>'.$item_content;
}
elseif ($blog['header_loc']==2){
$item_content = $item_content.'<p>Исходная запись опубликована здесь - <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>'.$nameofblog.'</a>. Вы можете оставить свой комментарий в этом журнале или <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>на сайте '.$_SERVER['HTTP_HOST'].'</a></p>';
}
else {$item_content = $item_content;}//можно не писать в принципе эту строчку
//Кросспостинг в ЖЖ
define('LJ_HOST',  $blog['journal']);
define('LJ_PATH',   '/interface/xmlrpc');
define('LJ_LOGIN',  $blog['loginlj']);//ваш_ЖЖ_логин
define('LJ_PASSWD', $blog['passlj']);//ваш_ЖЖ_пароль
 
// Создаем xml-rpc клиента
$ljClient = new IXR_Client(LJ_HOST, LJ_PATH);
 
// Посылаем challange-запрос
if (!$ljClient->query('LJ.XMLRPC.getchallenge')) {
    //echo 'Ошибка [' . $ljClient->getErrorpre().'] '.$ljClient->getErrorMessage();
	$msg = 'Не возможно соединиться с ЖЖ. Пожалуйста, проверьте настройки кросспостинга и доступен ли ЖЖ';
	cmsCore::addSessionMessage($msg, 'error');
}
else {
    // Получаем ответ
    $ljResponse = $ljClient->getResponse();
    // Вытягиваем challenge
    $ljChallenge = $ljResponse['challenge'];
 
    // Заполняем поля XML-запроса
    $ljArgs = array();
    // Имя пользователя
    $ljArgs['username']       = LJ_LOGIN;
    // Указываем способ идентификации
    $ljArgs['auth_method']    = 'challenge';
    // Указываем полученный challenge
    $ljArgs['auth_challenge'] = $ljChallenge;
    // Посылаем зафрованный пароль
    // формула md5(challenge + md5(password))
    //$ljArgs['auth_response']  = md5($ljChallenge . md5(LJ_PASSWD));//Так как у нас пароль уже заширован убираем дополнительное шифрование
	$ljArgs['auth_response']  = md5($ljChallenge . LJ_PASSWD);
    // Версия протокола, 1 - все данные в кодировке UTF-8
    $ljArgs['ver']            = "1";
    // Текст записи (перекодируем из windows-1251 в UTF-8)
    $ljArgs['event']          = iconv('windows-1251', 'UTF-8', $item_content);//текст записи
    // Заголовок записи (перекодируем из windows-1251 в UTF-8)
    $ljArgs['subject']        = iconv('windows-1251', 'UTF-8', $item_title);//заголовок
 
    // Дата
	$ljArgs['year']           = $datenow[year]; // год
    $ljArgs['mon']            = $datenow[mon]; // месяц
    $ljArgs['day']            = $datenow[mday]; // день
    $ljArgs['hour']           = $datenow[hours]; // часы
    $ljArgs['min']            = $datenow[minutes]; // минуты
 
    // Доп параметры
    $ljArgs['props']          = array(
                                    // Текст уже отформатирован (содержит HTML-теги)
                                    'opt_preformatted' => true,
                                    // Добавляем запись "задним числом"
                                    'opt_backdated'    => true,
                                    'taglist'          => iconv('windows-1251', 'UTF-8', $item_tags),//массив тегов
                                );
 
    // Доступность записи - доступна всем (по-умолчанию)
    $ljArgs['security']       = $blog['privacy'];
 
    // Добавляем новое сообщение
    $ljMethod = 'LJ.XMLRPC.postevent';
 
    // Посылаем запрос
    if (!$ljClient->query($ljMethod, $ljArgs)) {
        $error2 = $ljClient->getErrorMessage();
		$error = 'Постинг в ЖЖ окончился неудачей. Проверьте настройки кросспостинга.<br>Сервер ответил: ['.$ljClient->getErrorCode().'] '.$error2.'';
		cmsCore::addSessionMessage($error, 'error');
    }
    else {
    // Получаем ответ
    $ljID = $ljClient->getResponse();
	//Получаем настройки поста от ЖЖ
	//$ljID['itemid'] - идентификатор поста
	$postljid = $ljID['itemid'];
	//вставка идентификатора для последующего редактирования поста
	$this->inDB->query("UPDATE cms_blog_posts SET ljID='{$postljid}' WHERE id = '{$post_id}'");
	$msg = 'Постинг в ЖЖ прошел успешно';
	cmsCore::addSessionMessage($msg, 'success');

    }
}

}
}	

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function addBlogCategory($item){
        $item = cmsCore::callEvent('ADD_BLOG_CAT', $item);
        $sql = "INSERT INTO cms_blog_cats (blog_id, title)
                VALUES ('{$item['id']}', '{$item['title']}')";

        $result = $this->inDB->query($sql);

        $cat_id = $this->inDB->get_last_id('cms_blog_cats');

        return $cat_id ? $cat_id : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function updatePost($post_id, $item, $update_seo_link = 0){

        $item = cmsCore::callEvent('UPDATE_POST', $item);

        $item['id']         = $post_id;
		$seo_sql = '';
		// проверить seolink при редактировании поста
		if ($update_seo_link){
        $item['seolink']    = $this->getPostSeoLink($item);
			$seo_sql = ', seolink = "'.$item['seolink'].'"';
		}

        //парсим bb-код перед записью в базу
        $inCore                 = cmsCore::getInstance();
		// Парсим по отдельности части текста, если есть тег [cut
        if (strstr($item['content'], '[cut')){
            $msg_to 	= $this->getPostShort($item['content']);
			$msg_to 	= $inCore->parseSmiles($msg_to, true);
			$msg_after 	= $this->getPostShort($item['content'], false, true);
			$msg_after 	= $inCore->parseSmiles($msg_after, true);
			$cut        = $this->getPostCut($item['content']);
			$item['content_html'] = $msg_to . $cut . $msg_after;
        } else {
        $item['content_html']   = $inCore->parseSmiles($item['content'], true);

		}
		// Экранируем специальные символы
        $item['content']        = $this->inDB->escape_string($item['content']);
        $item['content_cod'] 	= $item['content_html'];//переменная для передачи в функцию кроспостинга
		$item['content_html']   = $this->inDB->escape_string($item['content_html']);
		
        $sql = "UPDATE cms_blog_posts
                SET cat_id={$item['cat_id']},
                    title='{$item['title']}',
                    feel='{$item['feel']}',
                    music='{$item['music']}',
                    content='{$item['content']}',
                    content_html='{$item['content_html']}',
                    allow_who='{$item['allow_who']}',
                    edit_times = edit_times+1,
                    edit_date = NOW(){$seo_sql},
					comments = '{$item['comments']}'
                WHERE id = $post_id";
        
        $result = $this->inDB->query($sql);

        cmsInsertTags($item['tags'], 'blogpost', $post_id);
		//если есть идентификатор поста в ЖЖ пробуем обновить и этот пост
		if ($item['ljID']) {$this->editPostLJ($item['blog_id'], $item['title'], $item['content_cod'], $item['tags'], $item['id'], $item['seolink'], $item['ljID']);}
        
		return true;
        
    }
/* ==================================================================================================== */
/* ========================редактирование поста в ЖЖ=================================================== */
public function editPostLJ($blog_id, $item_title, $item_content, $item_tags, $post_id, $item_seolink, $itemid){
// подключаем библиотеку для отправки сообщения в ЖЖ
require(PATH.'/plugins/p_ping/IXR_Library.php');
// вытаскиваем настройки блога
$sql = "SELECT *
		FROM cms_blogs
		WHERE id = '$blog_id'
		LIMIT 1";
$result = $this->inDB->query($sql);
$blog = $this->inDB->fetch_assoc($result);

if ($blog['crosspost']==1) {
//Разбиваем текст поста на 2 части по тегу [cut=...] и оставляем только первую из них
if (strstr($item_content, '[cut')){
//подкат в ЖЖ <lj-cut>текст скрываемой записи</lj-cut>

            $msg_to 	= $this->getPostShort($item_content);
			//$msg_to 	= $inCore->parseSmiles($msg_to, true);мы уже пропарсили код в предыдущей функции
			$msg_after 	= $this->getPostShort($item_content, false, true);
			//$msg_after 	= $inCore->parseSmiles($msg_after, true);мы уже пропарсили код в предыдущей функции
			//$cut        = $this->getPostCutLj($item_content);
			//$item_content = $msg_to . $cut . $msg_after;        
			if ($blog['more']==='link'){
			$item_content = $msg_to.'<br /><a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>Читать далее...</a>';
			} elseif ($blog['more']==='lj-cut') {
			$item_content = $msg_to.' <lj-cut>'.$msg_after.'</lj-cut>';
			} elseif ($blog['more']==='copy') {
			$item_content = $msg_to.$msg_after;
			}
}

//дата для отправки поста в журнал
$datenow = getdate();
foreach ( $datenow as $key => $val )
$nameofblog = $blog['custom_name_on'] == 1 ? $blog['custom_name'] : $blog['title'];
//if ($blog['custom_name_on'] == 1) {$nameofblog = $blog['custom_name'];} else {$nameofblog = $blog['title'];}
//запись в зависимости от настроек
if ($blog['header_loc']==1) {
$item_content = '<p>Исходная запись опубликована здесь - <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>'.$nameofblog.'</a>. Вы можете оставить свой комментарий в этом журнале или <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>на сайте '.$_SERVER['HTTP_HOST'].'</a></p>'.$item_content;
}
elseif ($blog['header_loc']==2){
$item_content = $item_content.'<p>Исходная запись опубликована здесь - <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>'.$nameofblog.'</a>. Вы можете оставить свой комментарий в этом журнале или <a target=_blank href=\'http://'.$_SERVER['HTTP_HOST'].'/blogs/'.$blog['seolink'].'/'.$item_seolink.'.html\'>на сайте '.$_SERVER['HTTP_HOST'].'</a></p>';
}
else {$item_content = $item_content;}//можно не писать в принципе эту строчку
//Кросспостинг в ЖЖ
define('LJ_HOST',  $blog['journal']);
define('LJ_PATH',   '/interface/xmlrpc');
define('LJ_LOGIN',  $blog['loginlj']);//ваш_ЖЖ_логин
define('LJ_PASSWD', $blog['passlj']);//ваш_ЖЖ_пароль

// Создаем xml-rpc клиента
$ljClient = new IXR_Client(LJ_HOST, LJ_PATH);
 
// Посылаем challange-запрос
if (!$ljClient->query('LJ.XMLRPC.getchallenge')) {
    //echo 'Ошибка [' . $ljClient->getErrorpre().'] '.$ljClient->getErrorMessage();
	$msg = 'Не возможно соединиться с ЖЖ. Пожалуйста, проверьте настройки кросспостинга и доступен ли ЖЖ';
	cmsCore::addSessionMessage($msg, 'error');
}
else {
    // Получаем ответ
    $ljResponse = $ljClient->getResponse();
    // Вытягиваем challenge
    $ljChallenge = $ljResponse['challenge'];
 
    // Заполняем поля XML-запроса
    $ljArgs = array();
    // Имя пользователя
    $ljArgs['username']       = LJ_LOGIN;
    // Указываем способ идентификации
    $ljArgs['auth_method']    = 'challenge';
    // Указываем полученный challenge
    $ljArgs['auth_challenge'] = $ljChallenge;
    // Посылаем зафрованный пароль
    // формула md5(challenge + md5(password))
    //$ljArgs['auth_response']  = md5($ljChallenge . md5(LJ_PASSWD));//Так как у нас пароль уже заширован убираем дополнительное шифрование
	$ljArgs['auth_response']  = md5($ljChallenge . LJ_PASSWD);
    // Версия протокола, 1 - все данные в кодировке UTF-8
    $ljArgs['ver']            = "1";
    // Текст записи (перекодируем из windows-1251 в UTF-8)
    $ljArgs['event']          = iconv('windows-1251', 'UTF-8', $item_content);//текст записи
    // Заголовок записи (перекодируем из windows-1251 в UTF-8)
    $ljArgs['subject']        = iconv('windows-1251', 'UTF-8', $item_title);//заголовок
 
    // Дата
	$ljArgs['year']           = $datenow[year]; // год
    $ljArgs['mon']            = $datenow[mon]; // месяц
    $ljArgs['day']            = $datenow[mday]; // день
    $ljArgs['hour']           = $datenow[hours]; // часы
    $ljArgs['min']            = $datenow[minutes]; // минуты
 
    // Доп параметры
    $ljArgs['props']          = array(
                                    // Текст уже отформатирован (содержит HTML-теги)
                                    'opt_preformatted' => true,
                                    // Добавляем запись "задним числом"
                                    'opt_backdated'    => true,
                                    'taglist'          => iconv('windows-1251', 'UTF-8', $item_tags),//массив тегов
                                );
 
    // Доступность записи - доступна всем (по-умолчанию)
    $ljArgs['security']       = $blog['privacy'];
 
    // Редактируем сообщение
    //$ljMethod = 'LJ.XMLRPC.postevent';
	$ljMethod = 'LJ.XMLRPC.editevent';
    $ljArgs['itemid'] = $itemid;
 
    // Посылаем запрос
    if (!$ljClient->query($ljMethod, $ljArgs)) {
        echo 'Ошибка ['.$ljClient->getErrorpre().'] '.$ljClient->getErrorMessage();
    }
    else {
    // Получаем ответ
    $ljID = $ljClient->getResponse();
	//Получаем настройки поста от ЖЖ
	//$ljID['itemid'] - идентификатор поста
	//$postljid = $ljID['itemid'];
	//вставка идентификатора для последующего редактирования поста
	//$this->inDB->query("UPDATE cms_blog_posts SET ljID='{$postljid}' WHERE id = '{$post_id}'");
	$msg = 'Редактирование поста в ЖЖ прошло успешно';
	cmsCore::addSessionMessage($msg, 'success');
    }
}

}
}	

/* ==================================================================================================== */
/* ==================================================================================================== */
/* ==================================================================================================== */
/* ==================================================================================================== */

    public function updateBlogCategory($cat_id, $item){
        $item = cmsCore::callEvent('UPDATE_BLOG_CAT', $item);
        $sql = "UPDATE cms_blog_cats
                SET title='{$item['title']}'
                WHERE id = $cat_id";
        $result = $this->inDB->query($sql);

        return true;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function deletePost($post_id){

        cmsCore::callEvent('DELETE_POST', $post_id);

        $inCore = cmsCore::getInstance();
        $inCore->loadLib('tags');
        $inCore->loadLib('karma');

        $sql = "SELECT p.blog_id as blog_id,
                       r.total_rating as rating
                FROM   cms_blog_posts p, cms_ratings_total r
                WHERE  r.item_id = {$post_id} AND r.target='blogpost'
                LIMIT 1";

        $res = $this->inDB->query($sql);
        if ($this->inDB->num_rows($res)){
            $post = $this->inDB->fetch_assoc($res);
            $this->inDB->query("UPDATE cms_blogs SET rating = rating - ({$post['rating']}) WHERE id = '{$post['blog_id']}'");
        }

        $this->inDB->query("DELETE FROM cms_blog_posts WHERE id = $post_id");
        $this->inDB->query("DELETE FROM cms_tags WHERE target='blogpost' AND item_id = '$post_id'");

        $inCore->deleteRatings('blogpost', $post_id);
        $inCore->deleteComments('blog', $post_id);

        cmsClearTags('blogpost', $post_id);

        $inCore->deleteUploadImages($post_id, 'blog');
		cmsActions::removeObjectLog('add_post', $post_id);

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function publishPost($post_id, $flag=1){

        $this->inDB->query("UPDATE cms_blog_posts SET published = $flag WHERE id = $post_id");
        return true;
        
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function deleteBlog($blog_id){
        cmsCore::callEvent('DELETE_BLOG', $blog_id);
        $inCore = cmsCore::getInstance();
        $posts = $this->inDB->get_table('cms_blog_posts', 'blog_id = '.$blog_id);

        foreach($posts as $key=>$post){
             $this->deletePost($post['id']);
        }

        $this->inDB->query("DELETE FROM cms_blog_posts WHERE blog_id = $blog_id");
        $this->inDB->query("DELETE FROM cms_blogs WHERE id = $blog_id");
		cmsActions::removeObjectLog('add_blog', $blog_id);

        return true;
    }

    public function deleteBlogs($id_list) {
        foreach($id_list as $key=>$id){
            $this->deleteBlog($id);
        }
        return true;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function deleteBlogCategory($cat_id){
        cmsCore::callEvent('DELETE_BLOG_CAT', $cat_id);
        $inCore = cmsCore::getInstance();
        $posts = $this->inDB->get_table('cms_blog_posts', 'cat_id = '.$cat_id);
        foreach($posts as $key=>$post){
            $this->deletePost($post['id']);
        }
        $this->inDB->query("DELETE FROM cms_blog_cats WHERE id = $cat_id");
        return true;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

}