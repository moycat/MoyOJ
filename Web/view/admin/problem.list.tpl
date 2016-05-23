{extends file='admin/subpage.tpl'}
{block name='menu'}
    <div class="row">
        <div class="col-sm-7">
            <ul class="nav nav-pills">
                <li role="presentation" class="active">
                    <a href="/admin/problem">所有题目
                        <span class="badge">{$count['problem']}</span>
                    </a>
                </li>
                <li role="presentation"><a href="/admin/problem/add">添加题目</a></li>
            </ul>
        </div>
        <div class="col-sm-5">
            <form class="form-inline pull-right" method="post" action="/admin/problem/search">
                <select name="type" class="form-control">
                    <option value="id">#</option>
                    <option value="title">标题</option>
                    <option value="tag">标签</option>
                </select>
                <div class="input-group">
                    <input name="filter" type="search" class="form-control" placeholder="Search..."
                           aria-describedby="Search">
                      <span class="input-group-btn">
                        <button type="submit" class="btn btn-danger">
                            <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                        </button>
                      </span>
                </div>
            </form>
        </div>
    </div>
{/block}
{block name='content'}
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>标题</th>
                <th>AC/提交(通过/尝试)</th>
                <th>评测限制</th>
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            {foreach $problems as $problem}
                {if $problem['status'] == 1}
                    <tr>
                        {else}
                    <tr class="danger">
                {/if}
                <th scope="row">
                    {$problem['id']}
                </th>
                <td>
                    <a href="/problem/{$problem['id']}" target="_blank">{$problem['title']}</a>
                </td>
                <td>
                    <code>{$problem['ac_cnt']}/{$problem['submit_cnt']}</code>
                    (<code>{$problem['solve_cnt']}/{$problem['try_cnt']}</code>)
                </td>
                <td>
                    <code>{$problem['time_limit']}ms</code>/<code>{$problem['mem_limit']}MiB</code>({$problem['turn']})
                </td>
                <td>
                    <a class="btn btn-xs btn-info" href="/admin/problem/{$problem['id']}/edit" role="button">编辑</a>
                    <a class="btn btn-xs btn-default" href="#" role="button">详情</a>
                    {if $problem['status'] == 1}
                        <a class="btn btn-xs btn-warning" href="/admin/problem/{$problem['id']}/lock"
                           role="button">锁定</a>
                    {else}
                        <a class="btn btn-xs btn-primary" href="/admin/problem/{$problem['id']}/unlock"
                           role="button">解锁</a>
                    {/if}
                    <a class="btn btn-xs btn-danger" href="/admin/problem/{$problem['id']}/delete" role="button">删除</a>
                </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
    //TODO: 分页
{/block}