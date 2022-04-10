<?php
namespace Pctco\Taglib;
use think\template\TagLib;
class Ui extends TagLib{
   protected $tags   =  [
      /**
      * @param close  0 = 闭合标签  1 = 不闭合
      **/

      'advertise'     => ['attr' => 'id,div', 'close' => 0],
      'article' => ['attr' => 'category,order,limit', 'close' => 1],
   ];
   public function tagAdvertise($tag){
      $result = '';

      $ad = new \app\model\Advertise;
      $id = $tag['id'];
      $div = $tag['div'] ?? '';

      $data = $ad->where('id',$id)->field('group,content,status')->find();

      if (empty($data)) return $result;

      // 判断定时广告是否到期
      // if ($data->cycle == 2) if ($data->stime >= $data->etime) return $result;

      $status = $data->status;
      $group = $data->group;

      if ($status == 1) {
         switch ($group) {
            case 'code':
               $result = htmlspecialchars_decode($data->content);
               break;
            case 'text':
               $arr = json_decode($data->content,true);
               $result = '<a target="_blank" href="'.$arr['url'].'" style="'.$arr['style'].'font-size:'.$arr['size'].'">'.$arr['name'].'</a>';
               break;
            case 'image':
               $arr = json_decode($data->content,true);

               foreach (['width','height','url','cover','alt'] as $name) {
                  if (empty($arr[$name])) $arr['alt'] = '';
               }

               
               
               $width = empty($arr['width'])?'':'width:'.$arr['width'].';';
               $height = empty($arr['height'])?'':'height:'.$arr['height'].';';
               $result = '<a target="_blank" href="'.$arr['url'].'" style="'.$width.$height.'"><img style="'.$width.$height.'" src="'.$arr['cover'].'" alt="'.$arr['alt'].'"></a>';
               break;
            case 'slideshow':
               $arr = json_decode($data->content,true);
               break;
         }
      }

      return $result;
   }

   public function tagArticle($tag,$content){
      $category = $tag['category'] ?? '';
      $order = $tag['order'] ?? 'id desc';
      $limit = $tag['limit'] ?? 10;

      $result = '<?php ';
      $result .= '$cache = new \think\facade\Cache;';
      $result .= '$skip = new \Pctco\Coding\Skip32\Skip;';
      $result .= '$date = new \Pctco\Date\Query;';


      if ($category) {
         $result .= '$article = \think\facade\Db::table("tco_article")->where("category","like","%,'.$category.',%")->limit('.$limit.')->order("'.$order.'")->select();';
      }else{
         $result .= '$article = \think\facade\Db::table("tco_article")->limit('.$limit.')->order("'.$order.'")->select();';
      }


      $result .= 'foreach ($article as $item) :';
      $result .= '$item["aid"] = $skip::en("article",$item["id"]);';


      $result .= '$c = $cache::store("article")->get($item["aid"].".md");';
      $result .= '$item["thumbnail"] = $c["thumbnail"];';
      $result .= '$item["category_name"] = $c["category_name"];';
      $result .= '$item["des"] = $c["des"];';
      $result .= '$item["time"] = $date::interval($item["atime"],["minutes","hours","days"]);';


      $result .= 'extract($item);?>';
      $result .= $content;
      $result .= '<?php endforeach ?>';
      return $result;
   }
}
