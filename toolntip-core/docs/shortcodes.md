| Component    | Shortcode                | Purpose                              |
| ------------ | ------------------------ | ------------------------------------ |
| Tool Icon    | `[tnt_tool_icon]`        | Current Tool logo/icon               |
| Tool Title   | `[tnt_tool_title]`       | Tool name/title                      |
| Tool Tagline | `[tnt_tool_tagline]`     | Tool tagline                         |
| Tool Meta    | `[tnt_tool_meta]`        | Tool metadata/status information     |
| Hero Image   | `[tnt_tool_hero_image]`  | Tool/Application featured image      |
| Features     | `[tnt_tool_features]`    | Existing features component          |
| Pros         | `[tnt_tool_pros]`        | Existing Pros component              |
| Cons         | `[tnt_tool_cons]`        | Existing Cons component              |
| Screenshots  | `[tnt_tool_screenshots]` | Existing screenshots/media component |
| Video        | `[tnt_tool_video]`       | Existing video/demo component        |
| FAQ          | `[tnt_tool_faq]`         | Existing FAQ component               |


[tnt_detail_features]
[tnt_detail_information]
[tnt_detail_similar_tools]
[tnt_detail_pros_cons]
[tnt_detail_faq]

[tnt_ad_leaderboard]
[tnt_ad_rectangle]
[tnt_ad_horizontal]
[tnt_ad_sidebar]
[tnt_ad_mobile]

Theme production

## ToolNTip Labs

```text
[tnt_labs]
```

Renders published Internal Tools using the existing Tool collection query engine and Tool Card component. Labs always enforces `tool_type = internal`; callers cannot override the Tool type.

Supported attributes:

```text
category=""
featured=""
limit="8"
columns="3"
orderby="date"
order="DESC"
```

Example:

```text
[tnt_labs category="developer-tools" featured="1" limit="6" columns="3"]
```
