﻿<?php
$db = new PDO("mysql:host=localhost;dbname=shop;charset=utf8", "root", "root");
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 添加菜单
if ($action == 'add') {
    $title  = $_POST['title'];
    $icon  = $_POST['icon'];
    $hide  = isset($_POST['hide']) ? $_POST['hide'] : 0;
    $url    = $_POST['url'];
    
    $statement = $db->prepare("insert into menu (title, icon, hide, url) values ('$title', '$icon', '$hide', '$url')");
    $statement->execute();

    if ($statement->rowCount()) {
	print_message(1, '添加菜单成功！');
    } else {
	print_message(0, '添加菜单失败！');
    }
}

// 编辑菜单
if ($action == 'edit') {
    $id	    = $_POST['id'];
    $title  = $_POST['title'];
    $icon  = $_POST['icon'];
    $hide  = isset($_POST['hide']) ? $_POST['hide'] : 0;
    $url    = $_POST['url'];
    
    $statement = $db->prepare("update menu set title='$title', icon='$icon', hide='$hide', url='$url' where id='$id'");
    $statement->execute();
    
    if ($statement->rowCount()) {
	print_message(1, '修改菜单成功！');
    } else {
	print_message(0, '未作任何修改，或修改失败！');
    }
}

// 删除菜单
if ($action == 'delete') {
    $id = $_POST['id'];
    
    $res = $db->query("select * from menu where pid='$id'")->fetch(PDO::FETCH_ASSOC);
    if ($res) {
	print_message(0, '菜单下有子菜单，无法删除！');
    }
    
    $statement = $db->prepare("delete from menu where id='$id'");
    $statement->execute();
    
    if ($statement->rowCount()) {
	print_message(1, '删除菜单成功！');
    } else {
	print_message(0, '删除菜单失败！');
    }
}

// 拖动编辑，ajax处理
if ( $action == 'drag' )
{
    $source         = $_POST['source'];
    $destination    = isset($_POST['destination']) ? $_POST['destination'] : 0;
    $orders         = isset($_POST['order']) ? json_decode($_POST['order']) : '';
    
    $statement = $db->prepare("update menu set pid='$destination' where id='$source'");
    $statement->execute();
    $affect = $statement->rowCount();

    $statement2 = $db->prepare("update menu set sort=:sort where id=:id"); 
    foreach($orders as $sort => $id){
        $statement2->bindParam(':sort', $sort);
        $statement2->bindParam(':id', $id);
        $statement2->execute();
    }
    $affect2 = $statement2->rowCount();
    
    if (!$affect && !$affect2) {
        print_message(0, '未做更改！');
    } else {
        print_message(1, '更改成功！');
    }
}

/**
 * 返回 ajax 数据
 * @param bool $status 状态，0-失败，1-成功
 * @param string $message 提示信息
 */
function print_message($status = 0, $message = '')
{
    $prompt = array('status' => $status, 'message' => $message);
  
    echo json_encode($prompt);
    exit;
}

/**
 * 从数据库获取menu数据，并递归调用buildMenu生成菜单
 * @global PDO $db
 * @return type
 */
function getMenu()
{
    global $db;
    $res = $db->query("select * from menu order by sort,id desc")->fetchAll(PDO::FETCH_ASSOC);

    return buildMenu($res);
}

/**
 * 构造菜单
 * @param type $menu
 * @param type $parentid
 * @return type
 */
function buildMenu($menu, $parentid = 0) 
{ 
  $result = null;
  foreach ($menu as $item) 
      
    if ($item['pid'] == $parentid) {
	$item_json = json_encode($item);
        $hide_node = $item['hide'] ? '[隐藏]' : '';
	$result .= "<li class='dd-item nested-list-item' data-id='{$item['id']}'>
      <div class='dd-handle nested-list-handle'>
	<span class='glyphicon glyphicon-move'></span>
      </div>
      <div class='nested-list-content'>
        {$item['title']}
        <span class='tip-msg'></span>
	<div class='pull-right'><span class='tip-hide'>{$hide_node}</span>
	  <a href='#editModal' class='edit_toggle' rel='{$item_json}'  data-toggle='modal'>编辑</a> |
	  <a href='#deleteModal' class='delete_toggle' rel='{$item['id']}' data-toggle='modal'>删除</a>
	</div>
      </div>" . buildMenu($menu, $item['id']) . "</li>";
    } 
  return $result ?  "\n<ol class=\"dd-list\">\n$result</ol>\n" : null;
}
