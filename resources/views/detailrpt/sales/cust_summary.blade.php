@inject('profiles', 'App\Profile')
@inject('custcategories', 'App\Custcategory')

<div ng-controller="custSummaryController">
<div class="col-md-12 col-xs-12">
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('profile_id', 'Profile', ['class'=>'control-label search-title']) !!}
                {!! Form::select('profile_id', [''=>'All']+
                    $profiles::filterUserProfile()
                        ->pluck('name', 'id')
                        ->all(),
                    null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.profile_id',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('current_month', 'Current Month', ['class'=>'control-label search-title']) !!}
                <select class="select form-control" name="current_month" ng-model="search.current_month" ng-change="searchDB()">
                    @foreach($month_options as $key => $value)
                        <option value="{{$key}}" selected="{{Carbon\Carbon::today()->month.'-'.Carbon\Carbon::today()->year ? 'selected' : ''}}">{{$value}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('status', 'Status', ['class'=>'control-label search-title']) !!}
                {!! Form::select('status', [''=>'All', 'Delivered'=>'Delivered', 'Confirmed'=>'Confirmed', 'Cancelled'=>'Cancelled'], null,
                    [
                    'class'=>'select form-control',
                    'ng-model'=>'search.status',
                    'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('id_prefix', 'ID Group', ['class'=>'control-label search-title']) !!}
                <select class="select form-group" name="id_prefix" ng-model="search.id_prefix" ng-change="searchDB()">
                    <option value="">All</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                    <option value="F">F</option>
                    <option value="G">G</option>
                    <option value="H">H</option>
                    <option value="R">R</option>
                    <option value="S">S</option>
                    <option value="V">V</option>
                    <option value="W">W</option>
                </select>
            </div>
        </div>
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('custcategory', 'Cust Category', ['class'=>'control-label search-title']) !!}
                <select name="custcategory" class="selectmultiple form-control" ng-model="search.custcategory" ng-change="searchDB()" multiple>
                    <option value="">All</option>
                    @foreach($custcategories::orderBy('name')->get() as $custcategory)
                    <option value="{{$custcategory->id}}">{{$custcategory->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>         
        <div class="col-md-4 col-xs-6">
            <div class="form-group">
                {!! Form::label('is_commission', 'Include Commission', ['class'=>'control-label search-title']) !!}
                {!! Form::select('is_commission', ['0'=>'No', ''=>'Yes'], null,
                    [
                        'class'=>'select form-control',
                        'ng-model'=>'search.is_commission',
                        'ng-change'=>'searchDB()'
                    ])
                !!}
            </div>
        </div>

    </div>
</div>

<div class="row" style="padding-left: 15px; padding-top:20px;">
    <div class="col-md-4 col-xs-12">
        <button class="btn btn-primary" ng-click="exportData()"><i class="fa fa-file-excel-o"></i><span class="hidden-xs"></span> Export Excel</button>
        <span ng-show="spinner"> <i style="color:red;" class="fa fa-spinner fa-2x fa-spin"></i></span>
    </div>
    <div class="col-md-4 col-xs-12">
        <div class="row">
            <div class="col-md-6 col-xs-6">
                Total Amount:
            </div>
            <div class="col-md-6 col-xs-6 text-right" style="border: thin black solid">
                <strong>@{{ total_amount ? total_amount : 0.00 | currency: "": 2}}</strong>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-xs-12 text-right">
        <div class="row">
            <label for="display_num">Display</label>
            <select ng-model="itemsPerPage" name="pageNum" ng-init="itemsPerPage='100'" ng-change="pageNumChanged()">
                <option ng-value="100">100</option>
                <option ng-value="200">200</option>
                <option ng-value="All">All</option>
            </select>
            <label for="display_num2" style="padding-right: 20px">per Page</label>
        </div>
        <div class="row">
            <label class="" style="padding-right:18px;" for="totalnum">Showing @{{alldata.length}} of @{{totalCount}} entries</label>
        </div>
    </div>
</div>

    <div class="table-responsive" id="exportable_custsummary" style="padding-top: 20px;">
        <table class="table table-list-search table-hover table-bordered">

            {{-- hidden table for excel export --}}
            <tr class="hidden">
                <td></td>
                <td data-tableexport-display="always">Total Amount</td>
                <td data-tableexport-display="always" class="text-right">@{{total_amount | currency: "": 2}}</td>
            </tr>
            <tr class="hidden" data-tableexport-display="always">
                <td></td>
            </tr>

            <tr style="background-color: #DDFDF8">
                <th class="col-md-1 text-center">
                    #
                </th>
                <th class="col-md-1 text-center">
                <a href="" ng-click="sortTable('custcategory')">
                    Category
                    <span ng-if="search.sortName == 'custcategory' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'custcategory' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                <a href="" ng-click="sortTable('custcategory_desc')">
                    Category Desc
                    <span ng-if="search.sortName == 'custcategory_desc' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'custcategory_desc' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('thistotal')">
                    Total<br>
                    (This Month)
                    <span ng-if="search.sortName == 'thistotal' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'thistotal' && search.sortBy" class="fa fa-caret-up"></span>
                </th>

                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prevtotal')">
                    Total<br>
                    (Last Month)
                    <span ng-if="search.sortName == 'prevtotal' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prevtotal' && search.sortBy" class="fa fa-caret-up"></span>
                </th>

                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prev2total')">
                    Total<br>
                    (Last 2 Month)
                    <span ng-if="search.sortName == 'prev2total' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prev2total' && search.sortBy" class="fa fa-caret-up"></span>
                </th>

                <th class="col-md-1 text-center">
                    <a href="" ng-click="sortTable('prevyeartotal')">
                    Total<br>
                    (Last Yr Same Mth)
                    <span ng-if="search.sortName == 'prevyeartotal' && !search.sortBy" class="fa fa-caret-down"></span>
                    <span ng-if="search.sortName == 'prevyeartotal' && search.sortBy" class="fa fa-caret-up"></span>
                </th>
            </tr>

            <tbody>
                <tr dir-paginate="transaction in alldata | itemsPerPage:itemsPerPage | orderBy:sortType:sortReverse" pagination-id="cust_summary" total-items="totalCount" current-page="currentPage">
                    <td class="col-md-1 text-center">
                        @{{ $index + indexFrom }}
                    </td>
                    <td class="col-md-1 text-center">
                        @{{ transaction.custcategory }}
                    </td>
                    <td class="col-md-1 text-left">
                        @{{ transaction.custcategory_desc }}
                    </td>
                    <td class="col-md-1 text-right">
                        @{{ transaction.thistotal | currency: "": 2 }}
                    </td>
                    <td class="col-md-1 text-right">
                        @{{ transaction.prevtotal | currency: "": 2 }}
                    </td>
                    <td class="col-md-1 text-right">
                        @{{ transaction.prev2total | currency: "": 2 }}
                    </td>
                    <td class="col-md-1 text-right">
                        @{{ transaction.prevyeartotal | currency: "": 2 }}
                    </td>
                </tr>
                <tr ng-if="!alldata || alldata.length == 0">
                    <td colspan="14" class="text-center">No Records Found</td>
                </tr>
            </tbody>
        </table>

        <div>
              <dir-pagination-controls max-size="5" pagination-id="cust_summary" direction-links="true" boundary-links="true" class="pull-left" on-page-change="pageChanged(newPageNumber)"> </dir-pagination-controls>
        </div>
    </div>
</div>