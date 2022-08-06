# WordPress FlipBoard Simple plugin

Create Flipboard RSS feed for WordPress

## Prestashop 1.7 free blog module
This free module allows you to create a blog on Prestashop 1.7

[You can make a donation to support the development of free modules by clicking on this link](https://www.paypal.com/donate?hosted_button_id=3CM3XREMKTMSE)

## Hooks for developpers
You can use actions hooks by hooking on these custom action hooks :
- actionBeforeEverBlogInitContent (params : int blog_post_number, array everpsblogposts, array evercategories, int page)
- actionBeforeEverCategoryInitContent (params : blog_category obj, array blog_posts)
- actionBeforeEverAuthorInitContent (params : obj blog_author)
- actionBeforeEverPostInitContent (params : blog_post obj, array blog_tags, array blog_products, obj blog_author)
- actionBeforeEverAuthorInitContent (params : obj blog_tag, array blog_posts)