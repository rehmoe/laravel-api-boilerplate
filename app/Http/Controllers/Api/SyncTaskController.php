<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexSyncTaskRequest;
use App\Http\Requests\StoreSyncTaskRequest;
use App\Http\Requests\UpdateSyncTaskRequest;
use App\Http\Transformers\Api\SyncTaskTransformer;
use App\Models\SyncTask;
use Dingo\Api\Exception\ValidationHttpException;

/**
 * @resource SyncTask
 *
 * @package App\Http\Controllers\Api
 */
class SyncTaskController extends ApiController
{
	/**
	 * SyncTaskController constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// User group restrictions
		$this->middleware('verifyUserGroup:Developer', ['only' => ['store', 'update', 'destroy']]);
		$this->middleware('verifyUserGroup:Developer,Support', ['only' => ['index', 'show']]);
	}

	/**
	 * Sync task list
	 *
	 * You can specify a GET parameter `root` (return only root tasks if true, children only if false) to filter results.<br />
	 * Filter results with `sync_task_status_id` GET parameter.
	 *
	 * @param IndexSyncTaskRequest $request
	 * @return \Dingo\Api\Http\Response
	 */
	public function index(IndexSyncTaskRequest $request)
	{
		// Root items filtering

		if ($request->has('root')) {
			if ($request->input('root')) {
				$query = SyncTask::parents()->applyRequestQueryString();
			} else {
				$query = SyncTask::children()->applyRequestQueryString();
			}
		} else {
			$query = SyncTask::applyRequestQueryString();
		}

		// Sync Task Status Id filtering

		if ($request->has('sync_task_status_id')) {
			$query = $query->where('sync_task_status_id', $request->input('sync_task_status_id'));
		}

		// Planned date bounding filtering

		if ($request->has('planned_before') || $request->has('planned_after')) {
			if ($request->has('planned_before')) {
				$this->where('planned_at', '<', $request->input('planned_before'));
			}
			if ($request->has('planned_after')) {
				$this->where('planned_at', '>', $request->input('planned_after'));
			}
		}

		$paginator = $query->withCount('childrenSyncTasks')
						   ->withCount('syncTaskLogs')
						   ->paginate();

		// sync_task_status_id

		return $this->response->paginator($paginator, new SyncTaskTransformer);
	}

	/**
	 * Get specified sync task
	 *
	 * @param string $syncTaskId Sync Task ID
	 * @return \Dingo\Api\Http\Response|void
	 */
	public function show($syncTaskId)
	{
		// @todo extend "authorized" everywhere
		
		$syncTask = SyncTask::authorized(['Owner', 'Administrator'])
							  ->withCount('childrenSyncTasks')
							  ->withCount('syncTaskLogs')
							  ->find($syncTaskId);

		if (!$syncTask)
			return $this->response->errorNotFound();

		return $this->response->item($syncTask, new SyncTaskTransformer);
	}

	/**
	 * Create and store new sync task
	 *
	 * @ApiDocsNoCall
	 *
	 * @param StoreSyncTaskRequest $request
	 * @return \Dingo\Api\Http\Response|void
	 * @throws ValidationHttpException
	 */
	public function store(StoreSyncTaskRequest $request)
	{
		$syncTask = SyncTask::create($request->all(), $request->getRealMethod());

		if ($syncTask) {
			// Register model transformer for created/accepted responses
			// @link https://github.com/dingo/api/issues/1218
			app('Dingo\Api\Transformer\Factory')->register(
				SyncTask::class,
				SyncTaskTransformer::class
			);

			return $this->response->created(
				app('Dingo\Api\Routing\UrlGenerator')
					->version('v1')
					->route('syncTask.show', $syncTask->id),
				$syncTask);
		}

		return $this->response->errorBadRequest();
	}

	/**
	 * Update a specified sync task
	 *
	 * @ApiDocsNoCall
	 *
	 * @param UpdateSyncTaskRequest $request
	 * @param string $syncTaskId Sync Task ID
	 * @return \Dingo\Api\Http\Response|void
	 */
	public function update(UpdateSyncTaskRequest $request, $syncTaskId)
	{
		$syncTask = SyncTask::find($syncTaskId);

		if (!$syncTask)
			return $this->response->errorNotFound();

		$syncTask->fill($request->all(), $request->getRealMethod());
		$syncTask->save();

		return $this->response->item($syncTask, new SyncTaskTransformer);
	}

	/**
	 * Delete specified sync task
	 *
	 * @ApiDocsNoCall
	 *
	 * @param string $syncTaskId Sync Task ID
	 * @return \Dingo\Api\Http\Response|void
	 */
	public function destroy($syncTaskId)
	{
		$syncTask = SyncTask::find($syncTaskId);

		if (!$syncTask)
			return $this->response->errorNotFound();

		$syncTask->delete();

		return $this->response->noContent();
	}
}
