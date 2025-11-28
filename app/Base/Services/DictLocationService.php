<?php


namespace App\Base\Services;

use App\Base\Exceptions\ApiException;
use App\Base\Services\BaseService;
use App\Base\Facades\DictLanguageFacade;
use App\Base\Models\DictLanguageModel;
use App\Base\Models\DictLocationModel;
use App\Base\Models\DictPlurilingualModel;
use App\Sys\Facades\SysI18nFacade;
use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\DB;

class DictLocationService extends BaseService
{
    private $dictLanguageModel;
    private $dictPlurilingualModel;
    /**
     * DictLocationService constructor.
     * @param DictLocationModel $model
     */
    public function __construct(DictLocationModel $model, DictLanguageModel $dictLanguageModel, DictPlurilingualModel $dictPlurilingualModel)
    {
        $this->model = $model;
        $this->dictLanguageModel = $dictLanguageModel;
        $this->dictPlurilingualModel = $dictPlurilingualModel;
    }

    /**
     * 地区列表
     * @param $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function lists($params){
        $name = $params['name'] ?? '';
        $limit = $this->getPageSize($params);
        $page = $params['page'] ?? 1;
        $orderBy = $params['sort'] ?? '';
        if (empty($orderBy)) {
            $orderBy = 'a.id desc';
        }
        $map = [];
        $OrWhere = [];
        $status = $params['status'] ?? '';
        if($status == '') {
            $map['a.status'] = [['in', [0, 1]]];
        }else{
            $map['a.status'] = $status;
        }
        $map['a.level'] = [['>=',2]];
        $model = $this->model->newInstance()->alias('a')
            ->where(function($q)use($OrWhere){
                foreach ($OrWhere as $or){
                    $q->orWhere($or[0],$or[1],$or[2]);
                }
            })
            ->buildQuery($map);
            
        if($name){
            $model = $model->whereRaw('(a.name like "%'.$name.'%" or dp.name like "%'.$name.'%")');
        }
        $list = $model
            ->leftJoin("dict_plurilingual as dp", function ($join) {
                $join->on("dp.relation_id", "=", "a.id")
                    ->where("dp.type", "=", DictPlurilingualModel::TYPE_LOCATION)
                    ->where("dp.language_id", "=", 2)
                    ->where("dp.status", "=", 0);
            })
            ->selectRaw("a.*,left(name_pinyin,1) AS PY,dp.name as en_name")
            ->orderByRaw($orderBy)
            ->get()->toArray();
        if ($name) {
            $list = $this->getLocationParents($list, 'pid', $orderBy);
        }
        $list = assoc_unique($list,'id'); // 按key去重

        if ($orderBy == 'PY asc' || $orderBy == 'PY desc') {
            foreach ($list as &$v) {
                $sortList[] = $v['PY'];
            }
            if ($orderBy == 'PY asc') {
                array_multisort($sortList, SORT_ASC, $list);
            } else {
                array_multisort($sortList, SORT_DESC, $list);
            }
        }
        $list = listToTree($list, 0, 'id',  'pid', $child = 'child');
        $counts = count($list);
        $return_data = [];
        $page_data = [];
        foreach($list as $item){
            $page_data[] = $item;
            if(count($page_data) == $limit){
                $return_data[] = $page_data;
                $page_data = [];
            }
        }
        if(!empty($page_data)){
            $return_data[] = $page_data;
        }
        return $this->paginator(count($return_data) ? $return_data[$page - 1] : [], $counts);
    }

    protected function getLocationParents($array,$key,$orderBy){
        $pids = array_column($array,$key);
        $pids = array_unique($pids);
        $parents_list = $this->model->newInstance()->db("a")
            ->leftJoin("dict_plurilingual as dp", function ($join) {
                $join->on("dp.relation_id", "=", "a.id")
                    ->where("dp.type", "=", DictPlurilingualModel::TYPE_LOCATION)
                    ->where("dp.language_id", "=", 2)
                    ->where("dp.status", "=", 0);
            })
            ->whereIn('a.id',$pids)
            ->where('a.level','>=',2)
            ->whereIn('a.status',[0, 1])
            ->selectRaw('a.*,left(name_pinyin,1) AS PY,dp.name as en_name')
            ->orderByRaw($orderBy)
            ->get()->toArray();
        if(empty($parents_list)){
            return $array;
        }else{
            $parents_list = $this->getLocationParents($parents_list,'pid', $orderBy);
            $data = array_merge($array,$parents_list);
            return $data;
        }
    }

    /**
     * 保存地区
     * @param $request
     * @return mixed
     */
    public function saveInfo($request){
        $params = $request->all();
        $id = $params['id'];
        //无ID则为新增
        $params['update_id'] = $this->getAuthAdminId();

        $log = [];
        $log['admin_id'] = $params['update_id'];
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $language_data = $params['languages'];
        $location_data = $params;
        unset($location_data['languages']);
        if($id){
            $this->updateBy(['id' => $id],$location_data);
            foreach($language_data as $language){
                $languages_where = [
                    'relation_id' => $id,
                    'type' => DictPlurilingualModel::TYPE_LOCATION,
                    'language_id' => $language['language_id']
                ];
                DictPlurilingualModel::updateOrCreate($languages_where,
                    array_merge($languages_where,['update_id'=>$params['update_id'],'name'=>$language['name']]));
            }
            DictPlurilingualModel::where('relation_id','=',$id)
                ->where('type','=',DictPlurilingualModel::TYPE_LOCATION)
                ->where('create_id','=',0)
                ->update(['create_id'=>$params['update_id']]);
            $log['operation'] = '更新数据：'.json_encode($params, JSON_UNESCAPED_UNICODE);
        }else{
            $params['create_id'] = $params['update_id'];
            $id = $this->save($params)->id;
            $parent = $this->findOneById($params['pid']);
            $params['id'] = $id;
            if($parent){
                $params['path'] = $parent->path.$id.',';
                $params['level'] = $parent->level + 1;
            }else{
                $params['path'] = ','.$id.',';
                $params['level'] = 2; // 默认是添加国家
            }
            // todo 更新path 与 level
            $this->updateBy(['id'=>$id],$params);
            // 增加多语言相关数据
            foreach($language_data as $language){
                $model = new DictPlurilingualModel();
                $model->relation_id = $id;
                $model->language_id = $language['language_id'];
                $model->name = $language['name'];
                $model->type = DictPlurilingualModel::TYPE_LOCATION;
                $model->status = 0;
                $model->update_id = $model->create_id = $params['create_id'];
                $model->save();
            }
            $log['operation'] = '新增数据：id => '.$id.'=>'.json_encode($params, JSON_UNESCAPED_UNICODE);
        }
        LogAdminOperationFacade::addOperationLog($log);
        return $id;
    }

    /**
     * 更改地区状态
     * @param $request
     * @return bool
     * @throws ApiException
     */
    public function changeStatus($request){
        $params = $request->all();
        $model = $this->findOneById($params['id']);
        if(!$model){
            throw new ApiException('common.no_records', '没有找到相关的记录');
        }
        $params['update_id'] = $this->getAuthAdminId();
        $ret = $this->updateBy(['id' => $params['id']],['status' => $params['status'],'update_id' => $params['update_id']]);
        $log = [];
        $log['admin_id'] = $params['update_id'];
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '更新状态：'. json_encode($params, JSON_UNESCAPED_UNICODE);
        LogAdminOperationFacade::addOperationLog($log);
        return $ret;
    }


    public function autoTrans()
    {
        set_time_limit(0);
        $list = $this->model->findBy([], 'id,name');
        $trans = $this->dictPlurilingualModel->db('b')
            ->leftJoin('dict_language as c', 'c.id', '=', 'b.language_id')
            ->where([
                'b.type'=>DictPlurilingualModel::TYPE_LOCATION,
                'b.status'=>0
            ])
            ->selectRaw('b.id,b.relation_id,b.language_id,b.name,c.code')
            ->get()->toArray();
        $languages = $this->dictLanguageModel->db()->get()->toArray();
        $languages = array_column($languages, 'id', 'code');
        $langCache = [];
        foreach ($trans as &$t) {
            $langCache[$t->relation_id][$t->code] = $t;
        }
        $langKeys = [
            'ru-ru'=>'ru',  // 俄语
            'ar-ar'=>'ara', // 阿位伯语
            'de-de'=>'de',  // 德语
            'ja-jp'=>'jp',  // 日语
            'ko-kr'=>'kor', // 韩语
            'ms-my'=>'may', // 马来语
            'es-es'=>'spa', // 西班牙语
            'id-id'=>'id',  // 印尼语
            'th-th'=>'th',  // 泰语
            'fr-fr'=>'fra', // 法语
            'pt-pt'=>'pt'   // 葡萄牙语
        ];
        $insertList = [];
        foreach ($list as &$v) {
            if ($v->id != 4184) {
                continue;
            }
            foreach ($langKeys as $lk=>$bk) {
                if (empty($langCache[$v->id][$lk])) {
                    $name = SysI18nFacade::translate($v->name, 'zh', $bk);
                    if ($name) {
                        $insertList[] = [
                            'relation_id'=>$v->id,
                            'language_id'=>$languages[$lk],
                            'name'=>$name,
                            'type'=>DictPlurilingualModel::TYPE_LOCATION
                        ];
                        if (count($insertList) > 200) {
                            $this->dictPlurilingualModel->insertAll($insertList);
                            $insertList = [];
                        }
                    }
                }
            }
        }
        if ($insertList) {
            $this->dictPlurilingualModel->insertAll($insertList);
        }
    }

    /**
     * 删除地区
     * @param $request
     * @return int
     */
    public function deleteInfo($request){
        $ids = $request->input('ids',0);
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $map = ['id' => [['in', $ids]]];
        $this->deleteBy($map, ['update_id' => $this->getAuthAdminId()]);
        $log = [];
        $log['admin_id'] = $this->getAuthAdminId();
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '删除数据：ids => '. implode(',',$ids);
        LogAdminOperationFacade::addOperationLog($log);
        return 1;
    }

    /**
     * 地区详情
     * @param $request
     * @return array|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function detail($request)
    {
        $id = $request->input('id',0);
        $info = $this->model->newInstance()->with('languages')->where('id','=',$id)->first();
        if ($info) {
            $info = $info->toArray();
        }
        $log = [];
        $log['admin_id'] = $this->getAuthAdminId();
        $log['ip'] = getClientIp();
        $log['url'] = $request->getRequestUri();
        $log['operation'] = '查看详情：id => '. $id;
        LogAdminOperationFacade::addOperationLog($log);
        return $info;
    }

    /**
     * 获取国家列表  level = 2
     * @return mixed
     */
    public function countryList($limit=10){
        $list = $this->model->newInstance()
            ->selectRaw('id,pid,code,code2,name')
            ->where('level','=',2)
            ->where('status','=',0)
            ->with('languages')
            ->limit($limit)
            ->orderBy('is_hot', "desc")
            ->get()->toArray();

        return $list;
    }

    /**
     * 当前地区是否有省份或者洲
     * @param $request
     * @return int
     */
    public function hasProvince($pid){
        $count = $this->model->newInstance()
            ->whereRaw('FIND_IN_SET('.$pid.',path)')
            ->where('level','>=',4)
            ->where('status' ,'=' ,0)
            ->count();
        return $count > 1 ? 1 : 0;
    }

    /**
     * 获取当前地区子节点集合
     * @param $request
     * @return mixed
     */
    public function getChild($pid,$limit = 10){
        $childs = $this->model->newInstance()
            ->where('pid','=',$pid)
            ->where('status','=',0)
            ->selectRaw('id,pid,code,name,level')
            ->with('languages')
            ->limit($limit)
            ->get()->toArray();
        if (!empty($childs[0]['level']) && $childs[0]['level'] === 4) {
            $childs = array_reverse($childs);
        }
        return $childs;
    }

    /**
     * 批量获取经纬度
     */
    public function batchGetLonLat()
    {
        set_time_limit(0);
        $list = $this->model->findBy(['path'=>['like', ',1,7,%'], '0'=>['level', '=', 4]], 'id,pid,name,level,code2');
        $cities = json_decode(file_get_contents(base_path('resources') . "/data/city.json"), true);
        $cacheCities = [];
        foreach ($cities as &$city) {
            if (empty($city['province'])) {
                continue;
            }
            $city['province'] = str_replace("省", "", $city['province']);
            $city['province'] = str_replace("区", "", $city['province']);
            $city['province'] = str_replace("市", "", $city['province']);
            $city['province'] = trim($city['province']);
            $city['city'] = str_replace("地区", "", $city['city']);
            if ($city["city"] !== '市辖区'
                && $city['city'] != '自治区直辖县级行政区划'
                && $city['city'] != '省直辖县级行政区划'
                && $city['city'] != '兰州市') {
                $city['city'] = str_replace( "区", "", $city['city']);
                $city['city'] = str_replace( "市", "", $city['city']);
            }
            $city['city'] = trim($city['city']);
            if (!strpos($city["area"], "新区")) {
                $city['area'] = str_replace( "区", "", $city['area']);
            }
            $city['area'] = str_replace( "县", "", $city['area']);
            $city['area'] = str_replace( "市", "", $city['area']);
            $city['area'] = trim($city['area']);
            if (empty($city['area'])) {
                if ($city['city'] === '市辖区') {
                    $cacheCities[$city['province']][$city['province']] = $city;
                }
                $cacheCities[$city['province']][$city['city']] = $city;
            } else {
                $cacheCities[$city['province']][$city['area']] = $city;
            }
        }
        foreach ($list as $k=>&$location) {
            echo "中国-".($k + 1)."\n";
            $province = $this->model->findOneById($location['pid'], 'id,pid,name,level,code2');
            $res = $cacheCities[$province['name']][$location['name']]??[];
            if (empty($res)) {
                foreach ($cacheCities as $pk=>$p) {
                    if (strpos($pk, $province['name']) === 0) {
                        $res = $p[$location['name']]??[];
                        if (empty($res)) {
                            foreach ($p as $ck=>$c) {
                                if (strpos($ck, $location['name']) === 0) {
                                    $res = $c;
                                    break;
                                }
                            }
                        }
                        break;
                    }
                }
            }
            if (!empty($res)) {
                $this->model->updateData([
                    'id'=>$location['id'],
                    'longitude'=>$res['lng'],
                    'latitude'=>$res['lat']
                ]);
            }
        }
        $list = $this->model->findBy(['longitude'=>0, '0'=>['level', '>', 2], '1'=>['level', '<', 5]], 'id,pid,name,level,code,code2');
        foreach ($list as $k=>$location) {
            echo "bing-".($k + 1)."\n";
            if ($location['level'] === 3) {
                $country = $this->model->findOneById($location['pid'], 'id,pid,name,level,code2');
                $lonLat = $this->getLonLat($country['code2'], '', $location['name']);
                $this->model->updateData([
                    'id'=>$location['id'],
                    'longitude'=>$lonLat['lon'],
                    'latitude'=>$lonLat['lat']
                ]);
            } else if ($location['level'] === 4) {
                $province = $this->model->findOneById($location['pid'], 'id,pid,name,level,code2');
                $country = $this->model->findOneById($province['pid'], 'id,pid,name,level,code2');
                $lonLat = $this->getLonLat($country['code2'], $province['name'], $location['name']);
                $this->model->updateData([
                    'id'=>$location['id'],
                    'longitude'=>$lonLat['lon'],
                    'latitude'=>$lonLat['lat']
                ]);
            }
        }
    }

    /**
     * 获取地址所在的经纬度
     * @param $address
     * @return mixed|null
     */
    public function getLonLatFormAddress($address)
    {
        $url = 'https://restapi.amap.com/v3/geocode/geo?key=2f5e88bea8c8ac4e7e94b5c3116dc2c0&address='.$address;
        $response = @file_get_contents($url);
        if (!$response) {
            return null;
        }
        $response = json_decode($response, true);
        $location = $response['geocodes'][0]['location']??'';
        if ($location) {
            $tmp = explode(',', $location);
            return ['position'=>[floatval($tmp[0]), floatval($tmp[1])]];
        }
        //百度查询经纬度
        /*$address = explode(" ", $address);
        $address = $address[1];
        $url = "https://api.map.baidu.com/place/v2/suggestion?query={$address}&region=1&output=json&ak=7ef3d7ed295ca59b56ec7c57e89f587b";
        $response = @file_get_contents($url);
        if (!$response) {
            return null;
        }
        $response = json_decode($response, true);
        $response = $response['result'][0]['location'] ?? [];
        if ($response) {
            return ['position'=>[floatval($response['lng']), floatval($response['lat'])]];
        }*/
        return null;
    }

    /**
     * 获取地址搜索结果
     */
    public function getAddressInputtips($keyword, $countryId = 7, $region = 1)
    {
        $keyword = str_replace("+", " ", $keyword);
        if (preg_match("/[\x7f-\xff]/", $keyword)) {
            /*$url = 'https://restapi.amap.com/v3/assistant/inputtips?key=' . config('app.amap.key') . '&datatype=poi&keywords=' . $keyword;
            $response = @file_get_contents($url);
            if (!$response) {
                return null;
            }
            $response = json_decode($response, true);
            $response = $response['tips'] ?? [];*/
            $keyword = str_replace(" ", "", $keyword);
            $url = "https://api.map.baidu.com/place/v2/suggestion?query={$keyword}&region={$region}&output=json&ak=1dcSyjv9iUjg3ilwdPIbp4Xbrb7LOuxr";
            $response = @file_get_contents($url);
            if (!$response) {
                return null;
            }
            $response = json_decode($response, true);
            $response = $response['result'] ?? [];
        } else {
            $locationInfo = $this->findOneById($countryId, "longitude,latitude");
            $keyword = urlencode($keyword);
            $url = "http://dev.virtualearth.net/REST/v1/Autosuggest?query={$keyword}&userCircularMapView={$locationInfo['latitude']},{$locationInfo['longitude']},5000&key=" . config('app.bing_map.key');
            $response = @file_get_contents($url);
            $response = json_decode($response, true);
            $response = $response['resourceSets'][0]['resources'][0]['value'] ?? [];
        }
        $ret = [];
        foreach ($response as $val) {
            if (!isset($val['name']) || in_array($val['name'], $ret)) {
                continue;
            }
            $ret[] = $val['name'];
        }
        return $ret;
    }

    /**
     * 获取地理位置
     * @param string $countryCode 国家代码
     * @param string $district 区域名字
     * @param string $city 城市
     * @param string $address 地址
     * @return array|int[]
     */
    public function getLonLat($countryCode, $district, $city = '', $address = '')
    {
        $bingUrl = "https://dev.virtualearth.net/REST/v1/Locations?CountryRegion=$countryCode&key=Q57tupj2UBsQNQdju4xL~xBceblfTd6icjljunbuaCw~AhwA-whmGMsfIpVhslZyknWhFYq-GvWJZqBnqV8Zq1uRlI5YM_qr7_hxvdgnU7nH";
        if ($district) {
            $bingUrl .= "&adminDistrict=$district";
        }
        if ($city) {
            $bingUrl .= "&locality=$city";
        }
        $response = @file_get_contents($bingUrl);
        $response = json_decode($response, true);
        $response = $response['resourceSets'][0]['resources'][0]['point']['coordinates'] ?? [];
        if ($response && $response[0] > 0 && $response[1] > 0) {
            return [
                'lat'=>$response[0],
                'lon'=>$response[1]
            ];
        } else {
            return [
                'lat'=>0,
                'lon'=>0,
            ];
        }
    }

    /**
     * 获取当前地区子集带搜索功能
     * @param $params
     * @return array
     */
    public function getLocationByCon($params)
    {
        $where = [];
        if(!empty($params['pid'])){
            if($params['pid'] != -1) {
                if(is_array($params['pid'])){
                    $where['a.pid'] = array(['in', $params['pid']]);
                } else {
                    $where['a.pid'] = $params['pid'];
                }
            }
        } else {
            $where['a.pid'] = 0;
        }
        if(!empty($params['level'])) {
            if(is_array($params['level'])) {
                $where['a.level'] = array(['in', $params['level']]);
            } else {
                $where['a.level'] = $params['level'];
            }
        }
        // 过滤部分id
        if (!empty($params['filter_ids']) && is_array($params['filter_ids'])) {
            $where['a.id'] = array(['notIn', $params['filter_ids']]);
        }
        if (!empty($params['country_ids']) && is_array($params['country_ids'])) {
            $where['a.id'] = array(['in', $params['country_ids']]);
        }
        if (!empty($params['is_hot'])) {
            $where['a.is_hot'] = 1;
        }
        $where['a.status'] = 0;
        $page = !empty($params['page']) ? $params['page'] : 1;
        $pageSize = !empty($params['page_size']) ? $params['page_size'] : 10;
        $fields="a.id,a.pid,a.code,a.code2,a.name,a.name_pinyin,a.path,b.name AS exchange_name";
        if (!empty($params['lat_lon'])) {
            $fields .= ',a.latitude as lat,a.longitude as lon';
        }
        $currLang = app('translator')->getLocale();
        $languageId = $this->dictLanguageModel->languageIdByCode($currLang);
        $model = $this->model->newInstance()->alias('a')
                     ->leftJoin('dict_plurilingual AS b', function ($join) use ($languageId){
                         $join->on('a.id', '=', 'b.relation_id')
                              ->where('b.type', '=', DictPlurilingualModel::TYPE_LOCATION)
                              ->where('b.status', '=', 0)
                              ->where('b.language_id', '=', $languageId);
                     })->buildQuery($where);
        if (!empty($params['en_search']) && !empty($params['keyword'])) {
            $model = $model->leftJoin("dict_plurilingual as dp", function ($join) {
                $join->on("dp.relation_id", "=", "a.id")
                    ->where("dp.type", "=", DictPlurilingualModel::TYPE_LOCATION)
                    ->where("dp.language_id", "=", 2)
                    ->where("dp.status", "=", 0);
            })->where(function ($join) use ($params) {
                $join->where('a.name', 'like', '%'.addslashes($params['keyword']).'%');
                $join->orWhere('dp.name', 'like', '%'.addslashes($params['keyword']).'%');
           });
        } else if(!empty($params['keyword'])) {
            $model = $model->where(function ($join) use ($params) {
                 $join->where('a.name', 'like', '%'.addslashes($params['keyword']).'%');
                 $join->orWhere('b.name', 'like', '%'.addslashes($params['keyword']).'%');
            });
        }
        $model = $model->selectRaw($fields);

        if(!empty($params['is_all'])){
            if (!empty($params['show_regions'])) {
                $model = $model->orWhereIn('a.name', ['香港', '台湾']);
            }
            $data = $model->groupBy('a.id')->get()->toArray();
            $pIds = array_column($data, 'id');
            $pIds = array_unique($pIds);
            foreach ($data as &$reValue) {
                if(!empty($reValue['exchange_name'])) {
                    $tmp = $reValue['name'];
                    $reValue['name'] = $reValue['exchange_name'];
                    $reValue['exchange_name'] = $tmp;
                }
                if (!empty($params['lat_lon']) && intval($reValue['lat']) === 0 && intval($reValue['lon']) === 0) {
                    $parent = $this->model->findOneById($reValue['pid']);
                    $lonLat = [];
                    if ($parent['level'] == 2) {
                        $lonLat = $this->getLonLat($parent['code'], '', $reValue['name']);
                    } else if ($parent['level'] == 3) {
                        $country = $this->model->findOneById($parent['pid']);
                        $lonLat = $this->getLonLat($country['code'], $parent['name'], $reValue['name']);
                    }
                    if ($lonLat) {
                        $this->model->updateData([
                            'id'=>$reValue['id'],
                            'longitude'=>$lonLat['lon'],
                            'latitude'=>$lonLat['lat'],
                        ]);
                        $reValue['lon'] = $lonLat['lon'];
                        $reValue['lat'] = $lonLat['lat'];
                    }
                }
            }
            if(!empty($pIds)){
                $hasChildData=$this->getHasChildData($pIds);
                foreach ($data as &$value){
                    if(!empty($hasChildData[$value['id']])){
                        $value['has_child']=1;
                    }else{
                        $value['has_child']=0;
                    }
                }
            }
        } else {
            $counts = $model->count(DB::raw('DISTINCT(a.id)'));
            $list = $model->groupBy('a.id')->forPage($page, $pageSize)
                         ->get()->toArray();
            if(!empty($list)) {
                foreach ($list as &$rvalue) {
                    if(!empty($rvalue['exchange_name'])) {
                        $tmp = $rvalue['name'];
                        $rvalue['name'] = $rvalue['exchange_name'];
                        $rvalue['exchange_name'] = $tmp;
                    }
                }
            }
            $data = $this->paginator($list, $counts);
            $data = $data ? $data->toArray() : [];
        }
        if ($currLang == "en-us") {
            if (isset($data['data'])) {
                sortArrByField($data['data'], "name");
            } else {
                sortArrByField($data, "name");
            }

        }
        return $data;
    }

    /**
     * 通过id获取地址
     * @param $id
     * @return array
     */
    public function locationById($id, $fields = '*')
    {
        $return = [];
        if (!empty($id)) {
            $where = [];
            if (is_array($id)) {
                $where['id'] = array(['in', $id]);
            } else {
                $where['id'] = $id;
            }
            $where['status'] = 0;
            $return = $this->model->newInstance()
                                ->buildQuery($where)
                                ->selectRaw($fields)
                                ->get()->toArray();
        }
        return $return;
    }

    /**
     * 根据id获取全路径
     * */
    public function getAllPathById($id){
        $retData=[];
        $info=$this->model->where('id','=',$id)->first();
        if($info){
            $info=$info->toArray();
            $retData[]=$info;

            if(!empty($info['path'])){
                $path= trim($info['path'],',');
                $ids=explode(',',$path);
                $pIds=array_diff($ids,[$id]);
                if(!empty($pIds)){
                    $pData=$this->model->whereIn('id',$pIds)->get();
                    if($pData){
                        $pData=$pData->toArray();
                        $retData=$pData;
                        $retData[]=$info;
                    }
                }

            }
        }
        return $retData;
    }

    /**
     * 获取是否有子集
     * */
    private function getHasChildData($id){
        $where = [];
        if (is_array($id)) {
            $where['a.pid'] = array(['in', $id]);
        } else {
            $where['a.pid'] = $id;
        }
        $fields="count(1) as count,a.pid";
        $data = $this->model->newInstance()->alias('a')
            ->buildQuery($where)
            ->selectRaw($fields)
            ->groupBy('a.pid')->get();
        if($data){
            $data=$data->toArray();
            $data=mapByKey($data,'pid');
        }else{
            $data=[];
        }
        return $data;

    }

    /**
     * 获取全部地区数据
     * @return mixed
     */
    public function getLocations()
    {
        $where = [];
        $where['level'] = array(['>', 1]);
        $where['status'] = 0;
        $data = $this->model->newInstance()
                ->buildQuery($where)
                ->selectRaw('id,pid,name')
                ->get()->toArray();
        $data = listToTree($data, 0, 'id', 'pid', 'children');
        return $data;
    }

    /**
     * 随机获取一个国家数据
     */
    public function getOneCountryRand()
    {
        $info = $this->model->newInstance()->buildQuery([
            "level" => 2,
            "status" => 0
        ])->selectRaw('id,pid,name')->with('languages')->orderByRaw('rand()')->first();
        return empty($info['id']) ? [] : $info->toArray();
    }

    /**
     * 获取中国的一个城市数据
     */
    public function getChinaCityRand()
    {
        $info = $this->model->newInstance()->buildQuery([
            "level" => 3,
            'status' => 0,
            'pid' => 7
        ])->selectRaw('id,pid,name')->orderByRaw('rand()')->first();
        return empty($info['id']) ? [] : $info->toArray();
    }

    /**
     * 根据ip获取国家id
     */
    public function getCountryIdByIp()
    {
        $ip = getClientIp();
        if ($ip === '127.0.0.1') {
            $isoCode = "CN";
        } else {
            try {
                $reader = new Reader(base_path('resources') . "/data/country.mmdb");
                $record = $reader->country($ip);
                $isoCode = $record->country->isoCode;
            } catch (\Exception $e) {
                $isoCode = "CN";
            }
        }
        $countryId = $this->getFieldBy("id", [
            "code2" => $isoCode,
            "status" => 0
        ]);
        return $countryId;
    }

    /**
     * 设置热门
     */
    public function setHot($id)
    {
        $info = $this->model->findOneById($id, "id,is_hot");
        if (empty($info['id'])) {
            throw new ApiException("common.no_records", "没有找到相关的记录");
        }
        $data = [];
        $data['is_hot'] = $info['is_hot'] === 1 ? 0 : 1;
        return $this->model->updateData($data, ["id" => $id]);
    }

    /**
     * 根据名称获取id
     */
    public function getIdByName($name, $lang = 'zh-cn')
    {
        $langId = DictLanguageFacade::getFieldBy("id", [
            "code" => $lang
        ]);
        $info = $this->model->db('a')->selectRaw('a.id')
        ->leftJoin('dict_plurilingual as b', function ($join) use ($langId) {
            $join->on('a.id', '=', 'b.relation_id')
                ->where('b.type', '=', DictPlurilingualModel::TYPE_LOCATION)
                ->where('b.language_id', '=', $langId);
        })
        ->whereRaw('(a.name="'.$name.'" or b.name="'.$name.'")')->first();
        return $info;
    }
}
