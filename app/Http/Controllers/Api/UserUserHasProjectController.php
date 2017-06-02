<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexUserUserHasProjectRequest;
use App\Http\Transformers\Api\UserHasProjectTransformer;
use App\Models\User;

/**
 * @resource UserHasProject
 * @todo Security ?
 * @package App\Http\Controllers\Api
 */
class UserUserHasProjectController extends ApiController
{
	/**
	 * UserUserHasProject constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		// User group restrictions
		$this->middleware('verifyUserGroup:Developer,Support')->only('index,show');
	}

	/**
	 * User relationship between users and projects list
	 *
	 * You can specify a GET parameter `user_role_id` to filter results.
	 *
	 * @param IndexUserUserHasProjectRequest $request
	 * @param string $userId User UUID
	 * @return \Dingo\Api\Http\Response
	 */
	public function index(IndexUserUserHasProjectRequest $request, $userId)
	{
		$user = User::find($userId);

		if (!$user)
			return $this->response->errorNotFound();

		$paginator = $user->userHasProjects($request->input('user_role_id'))->applyRequestQueryString()->paginate();

		return $this->response->paginator($paginator, new UserHasProjectTransformer);
	}
}