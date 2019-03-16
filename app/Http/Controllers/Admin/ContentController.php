<?php
/**
 * @author  Eddy <cumtsjh@163.com>
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Model\Admin\Content;
use App\Model\Admin\Entity;
use App\Repository\Admin\ContentRepository;
use App\Repository\Admin\EntityFieldRepository;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ContentController extends Controller
{
    protected $formNames = [];

    protected $entity = null;

    public function __construct()
    {
        parent::__construct();
        $entity = request()->route()->parameter('entity');
        $this->entity = Entity::query()->findOrFail($entity);
        ContentRepository::setTable($this->entity->table_name);
        $this->breadcrumb[] = ['title' => '内容列表', 'url' => route('admin::content.index', ['entity' => $entity])];
    }

    /**
     * 内容管理-内容列表
     *
     */
    public function index($entity)
    {
        $this->breadcrumb[] = ['title' => $this->entity->name . '内容列表', 'url' => ''];
        Content::$listField = [
            'title' => '标题'
        ];
        return view('admin.content.index', [
            'breadcrumb' => $this->breadcrumb,
            'entity' => $entity,
            'entityModel' => $this->entity
        ]);
    }

    /**
     * 内容列表数据接口
     *
     * @param Request $request
     * @return array
     */
    public function list(Request $request, $entity)
    {
        $perPage = (int) $request->get('limit', 50);
        $condition = $request->only($this->formNames);

        $data = ContentRepository::list($entity, $perPage, $condition);

        return $data;
    }

    /**
     * 内容管理-新增内容
     *
     */
    public function create($entity)
    {
        $this->breadcrumb[] = ['title' => "新增{$this->entity->name}内容", 'url' => ''];
        return view('admin.content.add', [
            'breadcrumb' => $this->breadcrumb,
            'entity' => $entity,
            'entityModel' => $this->entity,
            'entityFields' => EntityFieldRepository::getByEntityId($entity)
        ]);
    }

    /**
     * 内容管理-保存内容
     *
     * @param ContentRequest $request
     * @return array
     */
    public function save(ContentRequest $request, $entity)
    {
        try {
            ContentRepository::add($request->only(
                EntityFieldRepository::getByEntityId($entity)->pluck('name')->toArray()
            ));
            return [
                'code' => 0,
                'msg' => '新增成功',
                'redirect' => true
            ];
        } catch (QueryException $e) {
            \Log::error($e);
            return [
                'code' => 1,
                'msg' => '新增失败：' . (Str::contains($e->getMessage(), 'Duplicate entry') ? '当前内容已存在' : '其它错误'),
                'redirect' => false
            ];
        }
    }

    /**
     * 内容管理-编辑内容
     *
     * @param int $id
     * @return View
     */
    public function edit($entity, $id)
    {
        $this->breadcrumb[] = ['title' => "编辑{$this->entity->name}内容", 'url' => ''];

        $model = ContentRepository::find($id);
        return view('admin.content.add', [
            'id' => $id,
            'model' => $model,
            'breadcrumb' => $this->breadcrumb,
            'entity' => $entity,
            'entityModel' => $this->entity,
            'entityFields' => EntityFieldRepository::getByEntityId($entity)
        ]);
    }

    /**
     * 内容管理-更新内容
     *
     * @param ContentRequest $request
     * @param int $id
     * @return array
     */
    public function update(ContentRequest $request, $entity, $id)
    {
        $data = $request->only(
            EntityFieldRepository::getByEntityId($entity)->pluck('name')->toArray()
        );
        try {
            ContentRepository::update($id, $data);
            return [
                'code' => 0,
                'msg' => '编辑成功',
                'redirect' => true
            ];
        } catch (QueryException $e) {
            return [
                'code' => 1,
                'msg' => '编辑失败：' . (Str::contains($e->getMessage(), 'Duplicate entry') ? '当前内容已存在' : '其它错误'),
                'redirect' => false
            ];
        }
    }

    /**
     * 内容管理-删除内容
     *
     * @param int $id
     */
    public function delete($entity, $id)
    {
        try {
            ContentRepository::delete($id);
            return [
                'code' => 0,
                'msg' => '删除成功',
                'redirect' => route('admin::content.index')
            ];
        } catch (\RuntimeException $e) {
            return [
                'code' => 1,
                'msg' => '删除失败：' . $e->getMessage(),
                'redirect' => route('admin::content.index')
            ];
        }
    }

}
