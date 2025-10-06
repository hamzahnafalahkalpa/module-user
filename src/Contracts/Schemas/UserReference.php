<?php

namespace Hanafalah\ModuleUser\Contracts\Schemas;

use Hanafalah\LaravelSupport\Contracts\Supports\DataManagement;
use Hanafalah\ModuleUser\Contracts\Data\UserReferenceData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @see \Hanafalah\ModuleUser\Schemas\UserReference
 * @method self setParamLogic(string $logic, bool $search_value = false, ?array $optionals = [])
 * @method self conditionals(mixed $conditionals)
 * @method array storeUserReference(?UserReferenceData $rab_work_list_dto = null)
 * @method bool deleteUserReference()
 * @method bool prepareDeleteUserReference(? array $attributes = null)
 * @method mixed getUserReference()
 * @method array viewUserReferenceList()
 * @method Collection prepareViewUserReferenceList(? array $attributes = null)
 * @method LengthAwarePaginator prepareViewUserReferencePaginate(PaginateData $paginate_dto)
 * @method array viewUserReferencePaginate(?PaginateData $paginate_dto = null)
 * @method array showUserReference(?Model $model = null)
 * @method Builder function userReference(mixed $conditionals = null)
 */
interface UserReference extends DataManagement {
    public function prepareShowUserReference(? Model $model = null, ? array $attributes = null): Model;
    public function prepareStoreUserReference(UserReferenceData $user_reference_dto): Model;
}
