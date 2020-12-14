<div class="panel row">
	<h3><i class="icon icon-bullhorn"></i> {l s='Group links' mod='personalsalesmen'}</h3>
	<p>
	<ul class="list-unstyled">
		{$count = 0}
		{foreach $grp_links as $group}
			{if $count == 0}
				<div class="col-md-2">
			{/if}
				<li><a href="{$removeGrouplink}{$group.id}"><i class="material-icons small text-danger">delete</i></a> {$group.display}</li>
			{if $count == 6}
				</div>
				{$count = 0}
			{else}
				{$count = $count +1}
			{/if}
		{/foreach}
	</ul>
	</p>
</div>

<div class="panel row">
	<h3><i class="icon icon-bell"></i> {l s='Customer links' mod='personalsalesmen'}</h3>
	<p>
		<ul class="list-unstyled">
			{$count = 0}
			{foreach $cstmr_links as $cstmr}
				{if $count == 0}
					<div class="col-md-2">
				{/if}
					<li><a href="{$removeCstmrlink}{$cstmr.id}"><i class="material-icons small text-danger">delete</i></a> {$cstmr.display}</li>
				{if $count == 6}
					</div>
					{$count = 0}
				{else}
					{$count = $count +1}
				{/if}
			{/foreach}
		</ul>
	</p>
</div>