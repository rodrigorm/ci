require 'net/http'

if ENV['ROOT']
  root_path = File.expand_path(ENV['ROOT'])
else
  root_path = Dir.pwd
end
app_path = File.join(root_path, 'app')
tmp_path = File.join(app_path, 'tmp')
tests_path = File.join(app_path, 'tests', 'selenium', 'cases')

files = Dir.glob(File.join(tests_path, '*.test.php'))
files.concat Dir.glob(File.join(app_path, '**', 'tests', 'selenium', 'cases', '*.test.php'))
files.uniq!

files.map! do |file|
  file.gsub(tests_path, '').gsub('.test.php', '').gsub(/^\/||\\/, '')
end

if ENV['BROWSERS']
  browsers = ENV['BROWSERS'].split(',')
else
  browsers = %w(*firefox *safari)
end

sessions = []
files.each do |file|
  browsers.each do |browser|
    prefix = browser.gsub(/[^\w\d]+/, '_').gsub(/(^_|_{2,})/, '').downcase
    package = prefix.split('_').each{|word| word.capitalize!}.join('')
    sessions.push :testcase => file, :browser => browser, :prefix => prefix, :package => package
  end
end

workers = []

for i in 1..3 do
  workers << Thread.new() do
    while !sessions.empty?
      session = sessions.shift
      testcase = session[:testcase]
      browser = session[:browser]
      prefix = session[:prefix]
      package = session[:package]
      puts `JUNIT_PREFIX="#{prefix}" JUNIT_PACKAGE="#{package}" cake -working "#{app_path}" -browser "#{browser}" ci_selenium app case #{testcase} 2>&1`
    end
  end
end

workers.each do |worker|
  worker.join
end