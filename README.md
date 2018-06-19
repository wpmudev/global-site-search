# Global Site Search

**INACTIVE NOTICE: This plugin is unsupported by WPMUDEV, we've published it here for those technical types who might want to fork and maintain it for their needs.**


Powerful post search that extends across every site on your WordPress Multisite or BuddyPress network.

* Search post across a network 
* Display avatars in search results 
* Multisite and BuddyPress integration 
* Automatically adds site search page 
* Powered by Post Indexer 
* Protects private blog content 

## Global Site Search taps the power of Post Indexer to enable a powerful network-wide post search.

Multisite and BuddyPress give no way to search through blogs across an entire network.

### Blazing fast results

Global Site Search utilizes our powerful Post Indexer plugin, that indexes all the posts across every site on your network, to create a blazing fast search of your entire network.

This plugin automatically adds a ‘Site Search’ page where guests and users can search through posts from every blog on the network.

![][38]

Simple design to fit every theme

Display blog avatars in the search results for a more sophisticated feel.

![][39]

No coding needed to get clean results

### Search your entire network

Add a powerful network-wide search tool to Multisite or BuddyPress in seconds and give users a new way to find and discover content on your network.

### To Get Started:

_Important Notes:_

* If you have an older version of this plugin installed in your _/mu-plugins/_ folder, please delete it.
* This plugin requires our Post Indexer plugin in order to work. Please install & configure Post Indexer first if you haven’t already done so!
* This plugin only searches posts on public blogs — any posts on private blogs (only visible to logged in users) aren’t searchable.
* To display avatars, you will either need a theme with that feature built-in, or use our Avatars plugin, or use BuddyPress.

Once installed and network-activated, you’ll find the settings under Settings > Network Settings in your network admin.

![Global Site Search Menu][44]

### To Configure

Scroll down on the Network Settings screen until you see the Site Search section.

![Global Site Search Settings][45]

Select the number of listings you want to display per page (i.e. the number of posts displayed) and adjust the color scheme if you want to.

Then choose which post types you’d like the plugin to search for.

* Note that you can either select one post type, or all post types.

### To Use

The plugin will auto-create a page called “Site Search” on your main blog where anyone can search through all your blogs.

![Global Site Search Page][46]

There is also a widget that can be used in any sidebar. Here’s what the widget looks like in the Twenty-Fourteen theme.

![Global Site Search Widget][47]

To search for posts across your entire network, simply add your search term and click Search.

![Global Site Search Results][48]

Voilà! Whether searching from the Site Search page or the widget, search results from all blogs in your network will display nicely paginated on the Site Search page.

### Additional Customization Options

If the layout & styling doesn’t quite fit with look & feel of your site, you can override the plugin default templates in your theme.

First locate the _/templates/_ folder in the plugin download. Inside you will find 2 files:

* _global-site-search.php_ – Displays the search results.
* _global-site-search-form.php_ – Displays the search form.

Simply copy those files to the root of your theme to modify them as you wish. Be careful not to remove any functions though.

If you prefer to display the site search form and results on a sub-site in your network instead of on the main site, add the following constant to your wp-config.php file:

`define( 'GLOBAL_SITE_SEARCH_BLOG', 1 );`

Remember to change the number to the ID of your sub-site. Note that this will also make the Site Search widget available on that sub-site.

We hope you enjoy using Global Site Search. If you have any issues with use or configuration, or have a feature request, please drop by our [community forums][49] where support staff and other members are standing by to lend a hand.

[38]: https://premium.wpmudev.org/wp-content/uploads/2009/09/searchfunction1.jpg
[39]: https://premium.wpmudev.org/wp-content/uploads/2009/09/sitesearch.png
[40]: https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/
[41]: https://premium.wpmudev.org/wpmu-manual/
[42]: https://premium.wpmudev.org/project/post-indexer "Post Indexer"
[43]: https://premium.wpmudev.org/project/avatars/ "WordPress Avatars Plugin - WPMU DEV"
[44]: https://premium.wpmudev.org/wp-content/uploads/2009/09/global-site-search-3101-menu.png
[45]: https://premium.wpmudev.org/wp-content/uploads/2009/09/global-site-search-3101-settings.png
[46]: https://premium.wpmudev.org/wp-content/uploads/2009/09/global-site-search-3101-page.png
[47]: https://premium.wpmudev.org/wp-content/uploads/2009/09/global-site-search-3101-widget.png
[48]: https://premium.wpmudev.org/wp-content/uploads/2009/09/global-site-search-3101-results.png
