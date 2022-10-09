# onion-api-v2
 An API for absurd satire and real headlines 

Spits out a JSON array that contains:
 - Satire The Onion headlines
 - Real 'Not The Onion' headlines

Satire articles are sourced from The Onion's [website](https://www.theonion.com/politics/news-in-brief).  
Real articles are sourced from Reddit: [r/nottheonion/](https://www.reddit.com/r/nottheonion/).

---

Notes:

 * Caches results in `data_cache.json`.
 * Assumes your server timezone is set to UTC for the timestamp, but this otherwise doesn't impact funcitonality at all.
 * Requires [`simple_html_dom.php`](https://simplehtmldom.sourceforge.io/docs/1.9/index.html).
