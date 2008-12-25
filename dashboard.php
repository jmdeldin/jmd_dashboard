<?php
$plugin = array(
    'version' => '0.2',
    'author' => 'Jon-Michael Deldin',
    'author_uri' => 'http://jmdeldin.com',
    'description' => 'Customizable dashboard.',
    'type' => 1,
);

if (0) {
?>

//inc <README.textile>

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

if (txpinterface === 'admin')
{
    global $siteurl, $textarray;
    add_privs('jmd_dashboard', 1);
    register_callback('jmd_dashboard', 'jmd_dashboard');
    ob_start('jmd_dashboard_tab');

    if (gps('p_password') && !gps('event'))
    {
        txp_status_header("302 Found");
        header("Location: http://{$siteurl}/textpattern/?event=jmd_dashboard");
        exit;
    }
    
    $i10n = array(
        'jmd_dashboard_tab' => 'Dashboard',
    );
    $textarray = array_merge($textarray, $i10n);
}

/**
 * Parses the form "jmd_dashboard".
 * 
 * @param string $event
 * @param string $step
 */
function jmd_dashboard($event, $step)
{
    pageTop(gTxt('jmd_dashboard_tab'));
    include_once txpath . DS . 'publish.php';
    if (empty($GLOBALS['pretext']))
    {
        $GLOBALS['pretext'] = array('id' => '', 'q' => '',);
    }
    $contents = safe_field("Form", "txp_form", "name = 'jmd_dashboard'");
    if ($contents === FALSE)
    {
        $contents = <<<FORM
<h1 style="text-align:center">
    Hey, you haven&#8217;t customized jmd_dashboard yet.
    <a href="?event=form&amp;step=form_edit&amp;name=jmd_dashboard">
        Do it now!
    </a>
</h1>

<div style="margin: 0 auto; width: 400px;">
    <h1>
        <txp:site_name/>: Last modified on <txp:jmd_dashboard_lastmod/>
    </h1>

    <h2>Recently published articles</h2>
    <txp:article_custom break="li" wraptag="ul">
        <txp:title/> &#8211;
        <txp:jmd_dashboard_edit>
            edit #<txp:article_id/>
        </txp:jmd_dashboard_edit>
    </txp:article_custom>

    <h2>Recent comments</h2>
    <txp:recent_comments break="li" wraptag="ul">
        <txp:comment_message/> &#8211; <txp:comment_name link="0"/>
        (<txp:jmd_dashboard_edit type="comment">edit</txp:jmd_dashboard_edit>)
    </txp:recent_comments>
</div>
FORM;
        safe_insert("txp_form", "Form='". doSlash($contents) ."',
            type='misc', name='jmd_dashboard'");
    }

    echo parse($contents);
}

/**
 * Inserts a tab in the top menu row.
 * 
 * @param string $buffer
 */
function jmd_dashboard_tab($buffer)
{
    $gTxt = 'gTxt';
    $dashTab = <<<EOD
<td class="tabdown">
    <a href="?event=jmd_dashboard" class="plain">
        {$gTxt('jmd_dashboard_tab')}
    </a>
</td>
EOD;
    if (gps('event') === 'jmd_dashboard')
    {
        $dashTab = str_replace('tabdown', 'tabup', $dashTab);
    }
    $pattern = '/<td valign="middle" style="width:368px">[^"]*?<\/td>/';
    preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE);

    return preg_replace($pattern, $matches[0][0] . $dashTab, $buffer);
}

/**
 * Creates an edit link to an article or comment.
 * 
 * @param array $atts
 * @param string $thing Link text.
 * @param string $atts['type'] Article or comment.
 * @param int $atts['id'] Article or comment ID
 */
function jmd_dashboard_edit($atts, $thing)
{
    extract(lAtts(array(
        'id' => '',
        'type' => 'article',
    ), $atts));
    if ($type === 'comment')
    {
        $id = ($id ? $id : $GLOBALS['thiscomment']['discussid']);
        $href = 'discuss&amp;step=discuss_edit&discussid=' . $id;
    }
    if ($type === 'article')
    {
        $id = ($id ? $id : $GLOBALS['thisarticle']['thisid']);
        $href = 'article&amp;step=edit&amp;ID=' . $id;
    }

    return href(parse($thing), '?event='. $href);
}

/**
 * Returns the last modified date of the most recent article.
 * 
 * @param array $atts
 * @param string $atts['format'] Format the date according to strftime format
 * @param bool $atts['gmt'] Set the date based on GMT or current locale
 */
function jmd_dashboard_lastmod($atts)
{
    extract(lAtts(array(
        'format' => '%Y-%m-%d',
        'gmt' => 0,
    ), $atts));
    $modDate = safe_field("unix_timestamp(LastMod)", "textpattern",
        "Posted <= now() ORDER BY Posted desc");
    $out = safe_strftime($format, $modDate, $gmt);

    return $out;
}

# --- END PLUGIN CODE ---

?>
