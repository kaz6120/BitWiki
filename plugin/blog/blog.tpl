#blognavi
{if $continuefrom != null}&#x5b;{$continuefrom}&#x5d;のつづき{/if}


{$text}

#right{ldelim}
[[つづきを書く>plugin_blog_continue:{$pagename}]]
Category: {$categorylist} - {$timestamp|date_format:"%Y-%m-%d %H:%M:%S"}{rdelim}
----
#right{ldelim}&trackback(){rdelim}

#comment(above)

#blognavi