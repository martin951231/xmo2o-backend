<?php
class Page{
	// 起始行数
    public $firstRow;
	//现在页数
	public $nowPage;
	//总页数
	public $totalPage;
	//总行数
	public $totalRows;
	//分页的条数
	public $page_rows;
    //是否显示跳转到对应页面
    public $show_go_page;

    //架构函数
	public function __construct($totalRows,$listRows){
		$this->totalRows = $totalRows;
		$this->nowPage  = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$this->listRows = $listRows;
		$this->totalPage = ceil($totalRows/$listRows);
		/*if($this->nowPage > $this->totalPage && $this->totalPage>0){
			$this->nowPage = $this->totalPage;
		}*/
		$this->firstRow = $listRows*($this->nowPage-1);
	}
    public function show(){
		if($this->totalRows == 0) return false;
		$now = $this->nowPage;
		$total = $this->totalPage;
		
		$url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?");
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params['page']);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
		$url .= '&page=';
		
		$str = '<div class="summary">'.L_('X1 条记录 X2/X3页',array('X1'=>$this->totalRows,'X2'=>$now,'X3'=>$total)).'</div>';
		$str.= '<div class="pager">';
		$str.= '<ul class="pagination" id="yw1">';
		if($now > 1){
			$str .= '<li class="prev"><a href="'.$url.($now-1).'"><i class="ace-icon fa fa-angle-double-left"></i></a></li>';
		}
		if($now!=1 && $now>4 && $total>6){
			$str .= '<li class="page"> … </li>';
		}
		for($i=1;$i<=5;$i++){
			if($now <= 1){
				$page = $i;
			}elseif($now > $total-1){
				$page = $total-5+$i;
			}else{
				$page = $now-3+$i;
			}
			if($page != $now  && $page>0){
				if($page<=$total){
					$str .= '<li class="page"><a href="'.$url.$page.'" title="'.L_('第X1页',$page).'">'.$page.'</a></li>';
				}else{
					break;
				}
			}else{
				if($page == $now) $str .= '<li class="page active"><a href="'.$url.$page.'" title="'.L_('第X1页',$page).'">'.$page.'</a></li>';
			}
		}
		if($total != $now && $now<$total-5 && $total>10){
			$str .= '<li class="page"> … </li><li><a href="'.$url.$total.'" title="'.L_('第X1页',$page).'">'.$total.'</a></li>';
		}
		if ($now != $total){
			$str .= '<li class="next"><a href="'.$url.($now+1).'"><i class="ace-icon fa fa-angle-double-right"></i></a></li>';
		}
		$str .= '</ul>';

        if ($total > 1 && $this->show_go_page){
            $str .= '&nbsp;&nbsp;'.L_("跳转到").'&nbsp;&nbsp;<input type="text" id="go_page" value="'.$now.'" style="line-height:2rem;width:50px;">&nbsp;&nbsp;页&nbsp;&nbsp;<input type="button" id="go_page_btn" value="'.L_("跳转").'" style="width: 50px;line-height: 2rem;cursor: pointer;"><script> var go_url="'.$url.'"; $("#go_page").bind("keypress",function(event){if(event.keyCode == "13"){var page= $("#go_page").val();window.location.href=go_url+page;return false;}}); $("#go_page_btn").click(function(){var page= $("#go_page").val();window.location.href=go_url+page;return false;})</script>';
        }

		$str .= '</div>';
		
		return $str;
    }
}
?>