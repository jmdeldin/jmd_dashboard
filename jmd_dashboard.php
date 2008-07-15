<?php
$plugin = array(
    'version' => '0.2',
    'author' => 'Jon-Michael Deldin',
    'author_uri' => 'http://jmdeldin.com',
    'description' => 'Customizable dashboard.',
    'type' => 1,
);
if (!defined('txpinterface')) include_once '../zem_tpl.php';

if (0) {
?>

# --- BEGIN PLUGIN HELP ---

h1. jmd_dashboard: Customizable dashboard

To modify the dashboard, edit the form @jmd_dashboard@. This form can contain Textpattern and plugin tags.

h2. Example dashboard

h3. Form: jmd_dashboard

bc. <txp:hide>Display pending articles</txp:hide>
<h2>Pending articles</h2>
<ul>
    <txp:article_custom form="jmd_dashboard_pending" status="pending"/>
</ul>

h3. Form: jmd_dashboard_pending

bc. <li>
    <txp:title/> &8211;
    <txp:jmd_dashboard_edit>edit #<txp:article_id/></txp:jmd_dashboard_edit>
</li>

h2. Tag reference

* "jmd_dashboard_edit":#jmd_dashboard_edit
* "jmd_dashboard_lastmod":#jmd_dashboard_lastmod

h4(#jmd_dashboard_edit). @<txp:jmd_dashboard_edit type="article|comment">edit</txp:jmd_dashboard_edit>@

This tag outputs an edit link for articles and comments. It must be called by article_custom or recent_comment in either a form or a container tag.

|_. Attribute |_. Available values |_. Default value |_. Description |
| @id@ | int | discussid or thisid | If unset, the plugin uses the current article or comment ID. |
| @type@ | article, comment | article | Creates a link to the edit screen of whichever @type@ is set. |

h4(#jmd_dashboard_lastmod). @<txp:jmd_dashboard_lastmod format="strftime" gmt="1"/>@

This tag displays the last modified date based on the most recent article.

|_. Attribute |_. Available values |_. Default value |_. Description |
| @format@ | "strftime":http://php.net/strftime | @%Y-%m-%d@ | Date format. |
| @gmt@ | 1, 0 | 0 | If set (1), the date is adjusted according to GMT. |

# --- END PLUGIN HELP ---

<?php
}

# --- BEGIN PLUGIN CODE ---

if (txpinterface == 'admin')
{
    add_privs('jmd_dashboard', 1);
    register_tab('extensions', 'jmd_dashboard', 'jmd_dashboard');
    register_callback('jmd_dashboard', 'jmd_dashboard');
    ob_start('jmd_dashboard_tab');

    if (gps('p_password') && !gps('event'))
    {
        $uri = 'http://' . $GLOBALS['siteurl'] . '/textpattern/index.php?event=jmd_dashboard';
        txp_status_header("302 Found");
        header("Location: $uri");
        exit;
    }

    global $textarray;
    $i10n = array(
        'jmd_dashboard_tab' => 'Dashboard',
    );
    $textarray = array_merge($textarray, $i10n);
}

/**
 * Parses the form jmd_dashboard.
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
    if (empty($contents))
    {
        $contents = <<<FORM
<h1 style="text-align:center">
    Hey, you haven&#8217;t customized jmd_dashboard yet.
    <a href="?event=form&amp;step=form_edit&amp;name=jmd_dashboard">
        Do it now!
    </a>
</h1>

<div style="width: 400px; margin: 0 auto;">
    <h1>
        <txp:site_name/>: Last modified on <txp:jmd_dashboard_lastmod/>
    </h1>

    <h2>Recently published articles</h2>
    <txp:hide>SVN/4.0.7 - use the awesome containers:
        <txp:article_custom break="li" wraptag="ul">
            <txp:title/> &#8211;
            <txp:jmd_dashboard_edit>
                edit #<txp:article_id/>
            </txp:jmd_dashboard_edit>
        </txp:article_custom>
    </txp:hide>
    <txp:hide>
        To add the edit link, drop an edit tag in a form and add the form att:
        <txp:article_custom form="my_edit_tag" limit="5"/>
    </txp:hide>
    <txp:article_custom limit="5"/>

    <h2>Recent comments</h2>
    <txp:hide>SVN/4.0.7:
        <txp:recent_comments break="li" wraptag="ul">
            <txp:comment_message/> &#8211; <txp:comment_name link="0"/>
            (<txp:jmd_dashboard_edit type="comment">edit</txp:jmd_dashboard_edit>)
        </txp:recent_comments>
    </txp:hide>
    <txp:hide>For the edit link, add a form attribute.</txp:hide>
    <txp:recent_comments />
</div>
FORM;
        safe_insert("txp_form", "Form = '". doSlash($contents) ."',
            type = 'misc', name = 'jmd_dashboard'");
    }

    echo parse($contents);
}

/**
 * Inserts a tab in the top menu row.
 * @param string $buffer Admin page contents.
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
    if (gps('event') == 'jmd_dashboard')
    {
        $dashTab = str_replace('tabdown', 'tabup', $dashTab);
    }
    $pattern = '/<td valign="middle" style="width:368px">[^"]*?<\/td>/';
    preg_match($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE);

    return preg_replace($pattern, $matches[0][0] . $dashTab, $buffer);
}

/**
 * Creates an edit link to an article or comment.
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
    if ($type == 'comment')
    {
        $id = ($id ? $id : $GLOBALS['thiscomment']['discussid']);
        $href = 'discuss&amp;step=discuss_edit&discussid=' . $id;
    }
    if ($type == 'article')
    {
        $id = ($id ? $id : $GLOBALS['thisarticle']['thisid']);
        $href = 'article&amp;step=edit&amp;ID=' . $id;
    }

    return href(parse($thing), '?event='. $href);
}

/**
 * Returns the last modified date of the most recent article.
 * @param array $atts
 * @param string $atts['format'] Format the date according to strftime format
 * @param boolean $atts['gmt'] Set the date based on GMT or current locale
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
