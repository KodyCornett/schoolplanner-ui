<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6 sm:p-8 text-gray-900">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Modulus Help</h1>
        <p class="text-gray-600 mb-8">Everything you need to know about creating and managing your study schedule.</p>

        <!-- Overview -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">What is Modulus?</h2>
            <p class="text-gray-600 leading-relaxed">
                Modulus automatically generates a personalized study schedule from your Canvas calendar.
                It analyzes your upcoming assignments and deadlines, then creates optimized work blocks
                that fit around your existing commitments. You can review and adjust the plan before
                exporting it to your favorite calendar app.
            </p>
        </section>

        <!-- Getting Started -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Getting Started</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    How do I get my Canvas ICS URL?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <ol class="list-decimal list-inside space-y-2 mt-2">
                        <li>Log in to your Canvas account</li>
                        <li>Go to <strong>Calendar</strong> in the left sidebar</li>
                        <li>Click <strong>Calendar Feed</strong> at the bottom of the page</li>
                        <li>Copy the URL that appears (it starts with <code class="bg-gray-100 px-1 rounded">https://</code> and ends with <code class="bg-gray-100 px-1 rounded">.ics</code>)</li>
                    </ol>
                    <p class="mt-3 text-sm text-amber-600">
                        Keep this URL private - anyone with this link can see your calendar.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    The 4-step process
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <div class="grid gap-4 mt-2">
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">1</span>
                            <div>
                                <h4 class="font-medium text-gray-800">Import</h4>
                                <p class="text-sm">Paste your Canvas ICS URL and configure your preferences (study hours, planning horizon, etc.)</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">2</span>
                            <div>
                                <h4 class="font-medium text-gray-800">Generate</h4>
                                <p class="text-sm">Modulus analyzes your assignments and creates an optimized study schedule</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">3</span>
                            <div>
                                <h4 class="font-medium text-gray-800">Preview</h4>
                                <p class="text-sm">Review your schedule on an interactive calendar. Drag, resize, or delete blocks as needed</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-semibold">4</span>
                            <div>
                                <h4 class="font-medium text-gray-800">Download</h4>
                                <p class="text-sm">Export your finalized plan as an ICS file and import it into Google Calendar, Apple Calendar, or Outlook</p>
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <!-- Import Settings -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Import Settings Explained</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Planning Horizon
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        The number of days into the future to plan for. A 14-day horizon means Modulus
                        will only schedule work blocks for assignments due within the next 2 weeks.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Tip:</strong> Start with 7-14 days if you prefer to focus on immediate deadlines.
                        Use 21-30 days for a longer-term view.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Soft Cap (hours/day)
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Your preferred daily study limit. Modulus tries to keep each day's work
                        blocks under this amount, but may exceed it if necessary to meet deadlines.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Example:</strong> With a 4-hour soft cap, most days will have around 4 hours of study time scheduled.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Hard Cap (hours/day)
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Your absolute maximum daily study limit. Modulus will never schedule
                        more than this many hours on a single day, even if it means some work
                        cannot be scheduled.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Example:</strong> An 8-hour hard cap ensures you never have more than a full workday of studying scheduled.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Skip Weekends
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        When enabled, Modulus will not schedule any work blocks on Saturdays or Sundays.
                        Your assignments will be spread across weekdays only.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Busy Calendar (optional)
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        If you have existing commitments (work, classes, activities), you can provide
                        a second ICS calendar with your busy times. Modulus will avoid scheduling
                        study blocks during these periods.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Tip:</strong> Export your Google Calendar or Outlook calendar as an ICS file to use as your busy calendar.
                    </p>
                </div>
            </details>
        </section>

        <!-- Understanding the Preview -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Understanding the Preview</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Calendar color coding
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <ul class="mt-2 space-y-2">
                        <li class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded bg-blue-500"></span>
                            <span><strong>Blue blocks</strong> - Scheduled study/work time</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded bg-red-500"></span>
                            <span><strong>Red markers</strong> - Assignment due dates</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded bg-gray-400"></span>
                            <span><strong>Gray blocks</strong> - Busy times (from your busy calendar)</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <span class="w-4 h-4 rounded bg-blue-700"></span>
                            <span><strong>Dark blue blocks</strong> - Anchored/locked work blocks</span>
                        </li>
                    </ul>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Switching views
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Use the view buttons at the top of the calendar to switch between:
                    </p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Week view</strong> - See a full week at a time with hourly slots</li>
                        <li><strong>Day view</strong> - Focus on a single day with more detail</li>
                        <li><strong>Month view</strong> - Overview of the entire month</li>
                    </ul>
                </div>
            </details>
        </section>

        <!-- Editing Your Plan -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Editing Your Plan</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Moving and resizing work blocks
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        <strong>Drag to move:</strong> Click and hold a work block, then drag it to a new time slot.
                    </p>
                    <p class="mt-2">
                        <strong>Resize:</strong> Hover over the top or bottom edge of a block until you see a resize cursor,
                        then drag to make the block longer or shorter.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    What are anchored blocks?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        When you manually move or resize a work block, it becomes <strong>anchored</strong> (shown in darker blue).
                        Anchored blocks are "locked" in place - if you regenerate your schedule or edit other blocks,
                        anchored blocks will not be moved or changed.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Why this matters:</strong> If you've carefully scheduled a study session at a specific time,
                        anchoring ensures it stays put even when other parts of your schedule adjust.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    How effort redistribution works
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Each assignment has a total estimated effort (in hours). When you delete or shorten a work block,
                        Modulus redistributes that time to other non-anchored blocks for the same assignment.
                        This ensures you still have enough time scheduled to complete the assignment.
                    </p>
                    <p class="mt-2">
                        <strong>Example:</strong> If an assignment has 6 hours of total effort spread across three 2-hour blocks,
                        and you delete one block, the remaining two blocks will each become 3 hours.
                    </p>
                    <p class="mt-2 text-sm text-amber-600">
                        Note: Anchored blocks are never adjusted during redistribution.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Deleting work blocks
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Click on a work block to select it, then click the delete button (trash icon) or press the Delete key.
                        The time from the deleted block will be redistributed to other blocks for the same assignment.
                    </p>
                </div>
            </details>
        </section>

        <!-- Exporting -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Exporting Your Schedule</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    What is an ICS file?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        ICS (iCalendar) is a universal calendar format supported by virtually all calendar applications.
                        When you download your study plan, you get an <code class="bg-gray-100 px-1 rounded">.ics</code> file
                        that can be imported into Google Calendar, Apple Calendar, Microsoft Outlook, and more.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Importing to Google Calendar
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <ol class="list-decimal list-inside space-y-2 mt-2">
                        <li>Open <a href="https://calendar.google.com" class="text-blue-600 hover:underline" target="_blank">Google Calendar</a></li>
                        <li>Click the gear icon and select <strong>Settings</strong></li>
                        <li>Select <strong>Import & Export</strong> from the left sidebar</li>
                        <li>Click <strong>Select file from your computer</strong> and choose your downloaded ICS file</li>
                        <li>Select which calendar to add the events to</li>
                        <li>Click <strong>Import</strong></li>
                    </ol>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Importing to Apple Calendar
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <ol class="list-decimal list-inside space-y-2 mt-2">
                        <li>Double-click the downloaded ICS file, or drag it onto the Calendar app</li>
                        <li>Choose which calendar to add the events to</li>
                        <li>Click <strong>OK</strong></li>
                    </ol>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Importing to Microsoft Outlook
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <ol class="list-decimal list-inside space-y-2 mt-2">
                        <li>Open Outlook and go to <strong>File > Open & Export > Import/Export</strong></li>
                        <li>Select <strong>Import an iCalendar (.ics) file</strong></li>
                        <li>Browse to your downloaded file and select it</li>
                        <li>Choose to open as a new calendar or import into an existing one</li>
                    </ol>
                </div>
            </details>
        </section>

        <!-- FAQ -->
        <section class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-3">Frequently Asked Questions</h2>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Why aren't all my Canvas assignments showing up?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Modulus only imports assignments within your planning horizon. If you set a 14-day horizon,
                        assignments due more than 14 days from now won't appear. You can increase the horizon in your
                        import settings.
                    </p>
                    <p class="mt-2">
                        Also check that your Canvas calendar feed includes all your courses - some courses may need to
                        be manually enabled in Canvas calendar settings.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Can I update my schedule after exporting?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Yes! You can create a new plan at any time with updated settings or preferences.
                        However, changes you make in Modulus won't automatically sync to your external calendar -
                        you'll need to re-export and re-import the ICS file.
                    </p>
                    <p class="mt-2 text-sm">
                        <strong>Tip:</strong> Some calendar apps will merge or duplicate events when re-importing.
                        Consider deleting the old study events before importing a new schedule.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    How is study time estimated for each assignment?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Modulus uses assignment metadata and heuristics to estimate effort. You can adjust
                        the total effort for any assignment in the preview by clicking on it and modifying the
                        effort hours. This will redistribute the work blocks accordingly.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    My Canvas ICS URL isn't working
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Make sure you copied the complete URL, including the <code class="bg-gray-100 px-1 rounded">https://</code> at the beginning
                        and <code class="bg-gray-100 px-1 rounded">.ics</code> at the end. The URL should look something like:
                    </p>
                    <p class="mt-2 text-sm bg-gray-100 p-2 rounded font-mono break-all">
                        https://canvas.instructure.com/feeds/calendars/user_abc123.ics
                    </p>
                    <p class="mt-2">
                        If you're still having issues, try generating a new calendar feed URL in Canvas.
                    </p>
                </div>
            </details>

            <details class="mb-4 border border-gray-200 rounded-lg">
                <summary class="px-4 py-3 cursor-pointer hover:bg-gray-50 font-medium text-gray-700">
                    Is my Canvas data secure?
                </summary>
                <div class="px-4 pb-4 text-gray-600">
                    <p class="mt-2">
                        Modulus only reads the calendar data from your Canvas ICS feed - it cannot access
                        your grades, submissions, or other Canvas content. Your ICS URL is stored securely and
                        only used to fetch calendar data when generating your study plan.
                    </p>
                </div>
            </details>
        </section>

        <!-- Still need help -->
        <section class="mt-12 p-6 bg-blue-50 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Still need help?</h2>
            <p class="text-gray-600">
                If you have questions not covered here, please reach out and we'll be happy to assist.
            </p>
        </section>
    </div>
</div>
