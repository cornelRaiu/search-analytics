(function (wp) {

  const el = wp.element.createElement;
  const __ = wp.i18n.__;
  const { InspectorControls, useBlockProps } = wp.blockEditor;
  const { Disabled, PanelBody, Placeholder, RangeControl, SelectControl, TextControl, ToggleControl } = wp.components;

  // A settings control bound to one block attribute. The __next props opt into the current control
  // styles, so WordPress 6.7 to 7.0 don't log deprecation notices.
  const control = function (props, Control, attribute, settings) {
    const value = props.attributes[attribute];

    return el(Control, Object.assign({
      key: attribute,
      __nextHasNoMarginBottom: true,
      __next40pxDefaultSize: true,
      onChange: function (newValue) {
        const attributes = {};
        attributes[attribute] = newValue;
        props.setAttributes(attributes);
      }
    }, Control === ToggleControl ? { checked: value } : { value: value }, settings));
  };

  // The controls have no bottom margin, so the panel spaces them out.
  const panel = function (title, controls) {
    return el(PanelBody, { key: title, title: title },
      el('div', { style: { display: 'grid', gap: '16px' } }, controls)
    );
  };

  const periodControls = function (props) {
    const amountLabels = {
      minute: __('Number of minutes', 'search-analytics'),
      day: __('Number of days', 'search-analytics'),
      week: __('Number of weeks', 'search-analytics'),
      month: __('Number of months', 'search-analytics')
    };
    // As in the shortcodes, any other unit means all time.
    const unit = amountLabels[props.attributes.unit] ? props.attributes.unit : '';

    return [
      control(props, SelectControl, 'unit', {
        label: __('Period', 'search-analytics'),
        value: unit,
        options: [
          { label: __('Minutes', 'search-analytics'), value: 'minute' },
          { label: __('Days', 'search-analytics'), value: 'day' },
          { label: __('Weeks', 'search-analytics'), value: 'week' },
          { label: __('Months', 'search-analytics'), value: 'month' },
          { label: __('All time', 'search-analytics'), value: '' }
        ]
      }),
      '' !== unit && control(props, RangeControl, 'amount', {
        label: amountLabels[unit],
        min: 1,
        max: 60
      })
    ];
  };

  // defaultLabels: the text above the list with and without "Only terms with results".
  const listControls = function (props, countAttribute, resultsAttribute, labelAttribute, defaultLabels) {
    return [
      control(props, RangeControl, countAttribute, {
        label: __('Number of terms', 'search-analytics'),
        min: 1,
        max: 50
      }),
      control(props, ToggleControl, resultsAttribute, {
        label: __('Only terms with results', 'search-analytics')
      }),
      props.attributes.showLabel && control(props, TextControl, labelAttribute, {
        label: __('Text above the list', 'search-analytics'),
        placeholder: props.attributes[resultsAttribute] ? defaultLabels[0] : defaultLabels[1]
      })
    ];
  };

  const displayControls = function (props, showLabelText) {
    return [
      control(props, ToggleControl, 'linkTerms', {
        label: __('Link terms to their search results', 'search-analytics')
      }),
      props.attributes.linkTerms && control(props, ToggleControl, 'countLinkClicks', {
        label: __('Count clicks on these links as searches', 'search-analytics'),
        help: props.attributes.countLinkClicks
          ? __('Visits from crawlers that follow the links are counted too.', 'search-analytics')
          : __('Not counted, so that the listed terms do not keep themselves on top.', 'search-analytics')
      }),
      control(props, ToggleControl, 'showLabel', {
        label: showLabelText
      })
    ];
  };

  const popularSearchesControls = function (props) {
    const attributes = props.attributes;

    return [
      panel(__('Settings', 'search-analytics'), periodControls(props).concat(
        displayControls(props, __('Show the text above each list', 'search-analytics'))
      )),
      panel(__('Most searched terms', 'search-analytics'), [
        control(props, ToggleControl, 'mostSearched', {
          label: __('Show the most searched terms', 'search-analytics')
        })
      ].concat(attributes.mostSearched ? listControls(props, 'mostSearchedCount', 'mostSearchedOnlyWithResults', 'mostSearchedLabel', [
        __('Most Searched Terms With Results', 'search-analytics'),
        __('Most Searched Terms', 'search-analytics')
      ]) : [])),
      panel(__('Logged-in visitors', 'search-analytics'), [
        control(props, ToggleControl, 'userSearches', {
          label: __('Show visitors their own last searches', 'search-analytics'),
          help: __('Needs "Save Search By User" on in Search Analytics > Settings.', 'search-analytics')
        })
      ].concat(attributes.userSearches ? listControls(props, 'userSearchesCount', 'userSearchesOnlyWithResults', 'userSearchesLabel', [
        __('Your Last Successful Searches', 'search-analytics'),
        __('Your Last Searches', 'search-analytics')
      ]) : []))
    ];
  };

  const latestSearchesControls = function (props) {
    const list = listControls(props, 'count', 'onlyWithResults', 'label', [
      __('Recently Searched Terms With Results', 'search-analytics'),
      __('Recently Searched Terms', 'search-analytics')
    ]);

    // The text field goes right under its toggle.
    return panel(__('Settings', 'search-analytics'), periodControls(props).concat(
      list.slice(0, 2),
      displayControls(props, __('Show the text above the list', 'search-analytics')),
      list.slice(2)
    ));
  };

  // The preview comes from the same PHP that renders the block on the site.
  const edit = function (name, inspectorControls) {
    const emptyPlaceholder = function () {
      const blockType = wp.blocks.getBlockType(name);

      return el(Placeholder, {
        icon: blockType.icon.src,
        label: blockType.title,
        instructions: __('No searches match these settings yet. Until there are some, the block shows nothing on your site.', 'search-analytics')
      });
    };

    return function (props) {
      return el('div', useBlockProps(),
        el(InspectorControls, null, inspectorControls(props)),
        el(Disabled, null,
          el(wp.serverSideRender, {
            block: name,
            attributes: props.attributes,
            // The wrapper above already has the block's colors, spacing and classes.
            skipBlockSupportAttributes: true,
            EmptyResponsePlaceholder: emptyPlaceholder
          })
        )
      );
    };
  };

  // Reads true/false values like PHP's FILTER_VALIDATE_BOOLEAN, which the shortcodes use.
  const toBoolean = function (value) {
    return /^(1|true|on|yes)$/i.test(value);
  };

  // fields: the shortcode attribute and the type of each block attribute. A third value is the
  // shortcode's default, where it is not the block's.
  const shortcodeTransforms = function (name, tag, fields) {
    const attributes = {};

    Object.keys(fields).forEach(function (attribute) {
      const shortcodeAttribute = fields[attribute][0];
      const type = fields[attribute][1];

      attributes[attribute] = {
        type: type,
        shortcode: function (shortcodeAttributes) {
          const value = shortcodeAttributes.named[shortcodeAttribute];

          if (undefined === value) {
            return fields[attribute][2];
          }

          if ('boolean' === type) {
            return toBoolean(value);
          }

          if ('integer' === type) {
            return parseInt(value, 10) || undefined;
          }

          return value;
        }
      };
    });

    return {
      from: [
        // A shortcode pasted into the editor, or in classic content converted to blocks.
        {
          type: 'shortcode',
          tag: tag,
          attributes: attributes
        },
        // A Shortcode block with only this shortcode in it.
        {
          type: 'block',
          blocks: ['core/shortcode'],
          isMatch: function (shortcodeBlock) {
            const text = (shortcodeBlock.text || '').trim();
            const match = wp.shortcode.next(tag, text);

            return !!match && match.content === text;
          },
          transform: function (shortcodeBlock) {
            const shortcodeAttributes = wp.shortcode.next(tag, shortcodeBlock.text).shortcode.attrs;
            const blockAttributes = {};

            Object.keys(attributes).forEach(function (attribute) {
              blockAttributes[attribute] = attributes[attribute].shortcode(shortcodeAttributes);
            });

            return wp.blocks.createBlock(name, blockAttributes);
          }
        }
      ]
    };
  };

  const save = function () {
    return null;
  };

  wp.blocks.registerBlockType('search-analytics/popular-searches', {
    edit: edit('search-analytics/popular-searches', popularSearchesControls),
    save: save,
    transforms: shortcodeTransforms('search-analytics/popular-searches', 'mwtsa_display_search_stats', {
      unit: ['unit', 'string'],
      amount: ['amount', 'integer'],
      mostSearched: ['most_searched', 'boolean'],
      mostSearchedCount: ['most_searched_count', 'integer'],
      mostSearchedOnlyWithResults: ['most_searched_only_with_results', 'boolean'],
      mostSearchedLabel: ['most_searched_label', 'string'],
      userSearches: ['user_searches', 'boolean'],
      userSearchesCount: ['user_searches_count', 'integer'],
      userSearchesOnlyWithResults: ['user_searches_only_with_results', 'boolean'],
      userSearchesLabel: ['user_searches_label', 'string'],
      linkTerms: ['link_terms', 'boolean', false],
      countLinkClicks: ['count_link_clicks', 'boolean'],
      showLabel: ['show_label', 'boolean'],
      className: ['wrapper_class', 'string']
    })
  });

  wp.blocks.registerBlockType('search-analytics/latest-searches', {
    edit: edit('search-analytics/latest-searches', latestSearchesControls),
    save: save,
    transforms: shortcodeTransforms('search-analytics/latest-searches', 'mwtsa_display_latest_searches', {
      unit: ['unit', 'string'],
      amount: ['amount', 'integer'],
      count: ['count', 'integer'],
      onlyWithResults: ['only_with_results', 'boolean'],
      label: ['label', 'string'],
      linkTerms: ['link_terms', 'boolean', false],
      countLinkClicks: ['count_link_clicks', 'boolean'],
      showLabel: ['show_label', 'boolean'],
      className: ['wrapper_class', 'string']
    })
  });

})(window.wp);
