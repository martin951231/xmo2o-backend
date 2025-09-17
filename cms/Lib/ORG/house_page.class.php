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
	//架构函数
	public function __construct($totalRows,$listRows,$not_reset=false,$second_total=0){
		$this->totalRows = $totalRows;
		$this->second_total = $second_total;
		$this->nowPage  = !empty($_GET['page']) ? intval($_GET['page']) : 1;
		$this->listRows = $listRows;
		$this->totalPage = ceil($totalRows/$listRows);
		if($this->nowPage > $this->totalPage && $this->totalPage>0 && !$not_reset){
			$this->nowPage = $this->totalPage;
		}
		$this->firstRow = $listRows*($this->nowPage-1);
	}
    public function show(){
		if($this->totalRows == 0) return false;
		$now = $this->nowPage;
		$total = $this->totalPage;
		fdump_api([$now,$total],'page_show_0512',1);
		$url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?");
        $parse = parse_url($url);
        if(isset($parse['query'])) {
            parse_str($parse['query'],$params);
            unset($params['page']);
            $url   =  $parse['path'].'?'.http_build_query($params);
        }
		if(strpos(strrev($url),'?') === 0){
		   $url .= 'page=';
		}else{
		   $url .= '&page=';
		}
		$str = '<span class="total">'.L_('X1 条记录 X2 / X3 页',array('X1'=>'<span id="row_count">'.($this->second_total ? $this->second_total : $this->totalRows).'</span>','X2'=>$now,'X3'=>$total)).'  </span>';
		if($now > 1){
			$str .= '<a href="'.$url.($now-1).'" class="prev" title="'.L_("上一页").'">'.L_("上一页").'</a>';
		}
		if($now!=1 && $now>4 && $total>6){
			$str .= '<a href="'.$url.'1" title="1">1</a><div class="page-numbers dots">…</div>';
		}
		if ($now==2){
		    $j=6;
        }else{
            $j=5;
        }
		for($i=1;$i<=$j;$i++){
			if($now <= 1){
				$page = $i;
			}elseif($now >= $total-1){
				$page = $total-5+$i;
			}else{
				$page = $now-3+$i;
			}
			if($page != $now  && $page>0){
				if($page<=$total){
					$str .= '<a href="'.$url.$page.'" title="'.L_("第X1页",$page).'" class="pga">'.$page.'</a>';
				}else{
					break;
				}
			}else{
				if($page == $now) $str.='<span class="current">'.$page.'</span>';
			}
            fdump_api([$page,$now,$total,$str],'page_show_0512',1);
		}
		if($total != $now && $now<$total-5 && $total>10){
			$str .= '<span class="dots">…</span><a href="'.$url.$total.'" title="'.L_('第X1页',$total).'">'.$total.'</a>';
		}
		if ($now != $total){
			$str .= '<a href="'.$url.($now+1).'" class="next">'.L_("下一页").'</a>';
		}
		if ($total > 1){
			$str .= L_("跳转到").'&nbsp;&nbsp;<input type="text" id="go_page" value="'.$now.'" style="width: 35px;height: 20px;">&nbsp;&nbsp;页&nbsp;&nbsp;<input type="button" id="go_page_btn" value="'.L_("跳转").'" style="width: 50px;height: 23px;cursor: pointer;"><script> var go_url="'.$url.'"; $("#go_page").bind("keypress",function(event){if(event.keyCode == "13"){var page= $("#go_page").val();window.location.href=go_url+page;return false;}}); $("#go_page_btn").click(function(){var page= $("#go_page").val();window.location.href=go_url+page;return false;})</script>';
		}
		
		return $str;
    }
}
?>