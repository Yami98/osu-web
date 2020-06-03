# Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
# See the LICENCE file in the repository root for full licence text.

import { createElement as el, PureComponent } from 'react'
import * as React from 'react'
import { a } from 'react-dom-factories'
import { SelectOptions } from 'select-options'

export class SpotlightSelectOptions extends PureComponent
  render: =>
    el SelectOptions,
      bn: 'spotlight-select-options'
      renderOption: @renderOption
      onItemSelected: @onItemSelected
      options: @props.options
      selected: @props.selected


  renderOption: ({ children, cssClasses, onClick, option }) =>
    a
      children: children
      className: cssClasses
      href: @href(option?.id)
      key: option?.id
      onClick: onClick


  href: (key) ->
    window.osu.updateQueryString(null, spotlight: key)


  onItemSelected: (item) =>
    Turbolinks.visit @href(item.id)
