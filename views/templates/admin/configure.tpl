<div class="panel">
	<h3><i class="icon icon-bullhorn"></i> {l s='Group links' mod='personalsalesmen'}</h3>
	<p>
	<ul class="list-unstyled">
		{foreach $grp_links as $group}
			<li><a href="{$removeGrouplink}{$group.id}"><i class="material-icons small text-danger">delete</i></a> {$group.display}</li>
		{/foreach}
	</ul>
	</p>
</div>

<div class="panel">
	<h3><i class="icon icon-bell"></i> {l s='Customer links' mod='personalsalesmen'}</h3>
	<p>
		<ul class="list-unstyled">
			{foreach $cstmr_links as $cstmr}
				<li><a href="{$removeCstmrlink}{$cstmr.id}"><i class="material-icons small text-danger">delete</i></a> {$cstmr.display}</li>
			{/foreach}
		</ul>
	</p>
</div>